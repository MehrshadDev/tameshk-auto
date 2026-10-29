<?php

namespace App\Workflow\Nodes\Ai;

use App\Credentials\CredentialResolver;
use App\Models\NodeRun;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Graph\NodeConfigGuard;
use App\Workflow\Registry\NodeRegistry;
use App\Workflow\Support\AgentMemory;
use App\Workflow\Support\LlmChatClient;
use App\Workflow\Support\TemplateEngine;
use Throwable;

class AiAgentNode implements NodeContract
{
    private const MAX_TOOL_STEPS = 6;

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.agent',
            name: 'AI Agent',
            category: NodeCategory::Ai,
            description: 'ایجنت خودمختار هوش مصنوعی با قابلیت اتصال به مدل زبانی، حافظه و ابزارهای اجرایی.',
            icon: 'brand:ai-agent',
            group: null,
            inputs: [HandleType::Main->value, HandleType::ChatModel->value, HandleType::Memory->value, HandleType::Tool->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'prompt' => [
                    'type'        => 'textarea',
                    'label'       => 'پرامپت کاربر',
                    'default'     => '{{ input.chatInput }}',
                    'placeholder' => '{{ input.chatInput }}',
                    'required'    => true,
                ],
                'system_prompt' => [
                    'type'     => 'textarea',
                    'label'    => 'دستورالعمل‌های سیستمی (System Instructions)',
                    'required' => false,
                ],
            ],
            color: 'violet',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $context->node->loadMissing('incomingEdges.sourceNode');

        $findSupplyNode = fn (string $handle) => $context->node->incomingEdges
            ->firstWhere('target_handle', $handle)
            ?->sourceNode;

        $isDisabled = fn ($node) => $node !== null && ! empty($node->config['disabled']);

        $modelNode = $findSupplyNode(HandleType::ChatModel->value);

        if (! $modelNode || $isDisabled($modelNode)) {
            throw NodeExecutionException::because(
                $modelNode
                    ? 'نود مدل چت غیرفعال است.'
                    : 'ایجنت هوش مصنوعی به هیچ مدل چتی متصل نیست.'
            );
        }

        $guard = app(NodeConfigGuard::class);
        $guard->assertComplete($modelNode, $context->run);

        $credential = $context->credential((int) ($modelNode->config['credential_id'] ?? 0));

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه API برای مدل هوش مصنوعی یافت نشد.');
        }

        $model = (string) ($modelNode->config['model'] ?? 'gemini-2.0-flash');
        $temperature = (float) ($modelNode->config['temperature'] ?? 0.4);

        $memoryNode = $findSupplyNode(HandleType::Memory->value);

        if ($isDisabled($memoryNode)) {
            $memoryNode = null;
        }

        $hasMemory = $memoryNode !== null;

        if ($hasMemory) {
            $guard->assertComplete($memoryNode, $context->run);
        }

        $memoryConfig = $hasMemory
            ? TemplateEngine::render($memoryNode->config ?? [], ['input' => $context->input])
            : [];

        $memorySize = (int) ($memoryConfig['memory_size'] ?? 5);
        $customSession = trim((string) ($memoryConfig['session_key'] ?? ''));

        $session = $customSession !== ''
            ? 'c:' . $customSession
            : 's:' . data_get($context->run->trigger_data, 'sessionId', 'default');

        $sessionKey = AgentMemory::key(
            $context->run->workflow->user_id,
            $context->run->workflow_id,
            $session,
        );

        $systemPrompt = trim((string) ($context->config['system_prompt'] ?? ''));
        $userPrompt = trim((string) ($context->config['prompt'] ?? ''));

        if ($userPrompt === '') {
            throw NodeExecutionException::because('پرامپت کاربر خالی است.');
        }

        $history = [];

        if ($hasMemory) {
            $context->emitStatus($memoryNode->id, 'running');

            $memStart = microtime(true);
            $history = AgentMemory::get($sessionKey);

            $memRun = $this->recordRun($context, $memoryNode->id, 'success', $this->elapsed($memStart), [
                'input_data'  => ['session' => $sessionKey],
                'output_data' => ['messages_count' => count($history)],
            ]);

            $context->emitStatus($memoryNode->id, 'node_success', $memRun);
        }

        $toolNodes = $context->node->incomingEdges
            ->where('target_handle', HandleType::Tool->value)
            ->map(fn ($edge) => $edge->sourceNode)
            ->filter(fn ($n) => $n !== null && ! $isDisabled($n))
            ->values();

        $registry = app(NodeRegistry::class);
        $toolsMap = [];
        $functionDeclarations = [];

        foreach ($toolNodes as $tNode) {
            $guard->assertComplete($tNode, $context->run);
            $executor = $registry->make($tNode->type);

            if ($executor instanceof ToolContract) {
                $rawName = $executor->toolName($tNode->config ?? []);
                $normalizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $rawName);
                $normalizedName = trim((string) $normalizedName, '_');

                if (empty($normalizedName)) {
                    $normalizedName = 'tool_' . $tNode->id;
                }

                $toolsMap[$normalizedName] = [
                    'node'     => $tNode,
                    'instance' => $executor,
                ];

                $functionDeclarations[] = [
                    'name'        => $normalizedName,
                    'description' => $executor->toolDescription($tNode->config ?? []),
                    'parameters'  => $executor->toolParameters($tNode->config ?? []),
                ];
            }
        }

        $messages = [];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => ($msg['role'] ?? 'user') === 'model' ? 'assistant' : $msg['role'],
                'text' => (string) ($msg['text'] ?? ''),
            ];
        }

        $messages[] = [
            'role' => 'user',
            'text' => $userPrompt,
        ];

        $step = 0;
        $finalOutputText = null;

        while ($step < self::MAX_TOOL_STEPS) {
            $step++;
            $modelStart = microtime(true);
            $context->log("فراخوانی مدل هوش مصنوعی ($model | مرحله: $step)");
            $context->emitStatus($modelNode->id, 'running');

            try {
                $llmResult = LlmChatClient::chat(
                    credential: $credential,
                    model: $model,
                    messages: $messages,
                    systemPrompt: $systemPrompt !== '' ? $systemPrompt : null,
                    temperature: $temperature,
                    tools: $functionDeclarations,
                );
            } catch (Throwable $e) {
                $this->failModel($context, $modelNode->id, $modelStart, $e->getMessage());
                throw $e instanceof NodeExecutionException
                    ? $e
                    : NodeExecutionException::because($e->getMessage());
            }

            $toolCalls = $llmResult['tool_calls'];

            if (! empty($toolCalls)) {
                $messages[] = $llmResult['raw_assistant_message'];

                foreach ($toolCalls as $call) {
                    $fnName = (string) ($call['name'] ?? '');
                    $fnArgs = (array) ($call['args'] ?? []);
                    $callId = (string) ($call['id'] ?? ('call_' . uniqid()));

                    if (! isset($toolsMap[$fnName])) {
                        $this->failModel($context, $modelNode->id, $modelStart, "ابزار ناشناخته: $fnName");
                        throw NodeExecutionException::because("ابزار درخواستی [$fnName] پیدا نشد.");
                    }

                    $toolInfo = $toolsMap[$fnName];
                    $targetToolNode = $toolInfo['node'];
                    $toolInstance = $toolInfo['instance'];

                    $context->log("اجرای ابزار [$fnName]");
                    $context->emitStatus($targetToolNode->id, 'running');
                    $toolStart = microtime(true);

                    $toolContext = new NodeContext(
                        node: $targetToolNode,
                        config: $targetToolNode->config ?? [],
                        input: $fnArgs,
                        run: $context->run,
                        credentials: app(CredentialResolver::class),
                        onProgress: $context->onProgress,
                    );

                    try {
                        $toolResult = $toolInstance->executeTool($toolContext, $fnArgs);

                        $toolRun = $this->recordRun($context, $targetToolNode->id, 'success', $this->elapsed($toolStart), [
                            'input_data'  => $fnArgs,
                            'output_data' => is_array($toolResult) ? $toolResult : ['result' => $toolResult],
                        ]);
                        $context->emitStatus($targetToolNode->id, 'node_success', $toolRun);
                    } catch (Throwable $e) {
                        if (! $e instanceof NodeExecutionException) {
                            report($e);
                        }

                        $toolError = $e instanceof NodeExecutionException
                            ? $e->getMessage()
                            : 'اجرای ابزار با خطا مواجه شد.';

                        $toolRun = $this->recordRun($context, $targetToolNode->id, 'failed', $this->elapsed($toolStart), [
                            'input_data' => $fnArgs,
                            'error'      => $toolError,
                        ]);
                        $context->emitStatus($targetToolNode->id, 'node_failed', $toolRun);

                        $toolResult = ['error' => $toolError];
                    }

                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $callId,
                        'name'         => $fnName,
                        'content'      => is_array($toolResult) ? $toolResult : ['result' => $toolResult],
                    ];
                }

                continue;
            }

            $finalOutputText = $llmResult['text'];

            if ($finalOutputText === '') {
                $this->failModel($context, $modelNode->id, $modelStart, 'پاسخ هوش مصنوعی خالی بود.');
                throw NodeExecutionException::because('پاسخ هوش مصنوعی خالی بود.');
            }

            $successRun = $this->recordRun($context, $modelNode->id, 'success', $this->elapsed($modelStart), [
                'input_data'  => ['prompt' => $userPrompt, 'model' => $model],
                'output_data' => ['response' => $finalOutputText],
            ]);

            $context->emitStatus($modelNode->id, 'node_success', $successRun);
            break;
        }

        if ($finalOutputText === null) {
            throw NodeExecutionException::because('ایجنت بدون تولید پاسخ نهایی از حداکثر مراحل اجرای ابزار عبور کرد.');
        }

        if ($hasMemory && $memorySize > 0) {
            $history[] = ['role' => 'user', 'text' => $userPrompt];
            $history[] = ['role' => 'model', 'text' => $finalOutputText];

            $maxEntries = $memorySize * 2;

            if (count($history) > $maxEntries) {
                $history = array_slice($history, -$maxEntries);
            }

            AgentMemory::put($sessionKey, $history);
        }

        return NodeResult::of([
            'response' => $finalOutputText,
            'model'    => $model,
            'history'  => $history,
            'input'    => $context->input,
        ]);
    }

    private function failModel(NodeContext $context, int $nodeId, float $startedAt, string $error): void
    {
        $run = $this->recordRun($context, $nodeId, 'failed', $this->elapsed($startedAt), [
            'error' => $error,
        ]);

        $context->emitStatus($nodeId, 'node_failed', $run);
    }

    private function recordRun(
        NodeContext $context,
        int $nodeId,
        string $status,
        int $durationMs,
        array $attributes = [],
    ): NodeRun {
        return NodeRun::create([
            'workflow_run_id' => $context->run->id,
            'node_id'         => $nodeId,
            'status'          => $status,
            'duration_ms'     => $durationMs,
            'started_at'      => now(),
            'finished_at'     => now(),
            ...$attributes,
        ]);
    }

    private function elapsed(float $startedAt): int
    {
        return max(1, (int) round((microtime(true) - $startedAt) * 1000));
    }
}
