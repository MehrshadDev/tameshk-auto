<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Enums\HandleType;
use App\Workflow\Registry\NodeRegistry;
use App\Workflow\Runner\WorkflowRunner;
use App\Workflow\Support\ChatReply;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Models\NodeRun;
use Throwable;

class ChatWebhookController extends Controller
{
    public function __construct(private readonly NodeRegistry $registry) {}

    public function show(Request $request, string $token): View
    {
        [, $trigger] = $this->resolve($request, $token);

        $config = $trigger->config ?? [];

        return view('pages.chat.public', [
            'title' => $this->text($config, 'title', 'گفتگو'),
            'placeholder' => $this->text($config, 'input_placeholder', 'پیام خود را بنویسید...'),
            'config' => [
                'token' => $token,
                'endpoint' => route('webhooks.chat.message', ['token' => $token], false),
                'initialMessage' => $this->text($config, 'initial_message', ''),
                'pendingText' => 'در حال نوشتن...',
                'errorText' => 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.',
                'rateLimitText' => 'تعداد پیام‌ها زیاد است. کمی صبر کنید.',
                'emptyText' => 'پاسخی دریافت نشد.',
            ],
        ]);
    }

    public function message(Request $request, string $token, WorkflowRunner $runner): JsonResponse
    {
        [$workflow, $trigger] = $this->resolve($request, $token);

        $data = $request->validate([
            'chatInput' => ['required', 'string', 'max:3000'],
            'sessionId' => ['nullable', 'string', 'max:100'],
        ]);

        set_time_limit(180);

        try {
            $run = $runner->run($workflow, [
                'chatInput' => $data['chatInput'],
                'sessionId' => $data['sessionId'] ?? (string) Str::uuid(),
            ]);

            if ($run->status !== 'success') {
                return $this->failure();
            }

            $lastId = $run->nodeRuns()
                ->whereNotIn('node_id', $this->supplyNodeIds($workflow))
                ->max('id');

            $last = $lastId ? NodeRun::query()->find($lastId) : null;

            $reply = $last && $last->node_id !== $trigger->id
                ? ChatReply::extract($last->output_data)
                : '';

            return response()->json(['reply' => $reply]);
        } catch (Throwable $e) {
            report($e);

            return $this->failure();
        }
    }

    private function resolve(Request $request, string $token): array
    {
        $workflow = Workflow::query()->where('webhook_token', $token)->first();

        if (! $workflow || ! $workflow->isPublished() || ! $workflow->is_active) {
            throw $this->notRegistered($request, $token);
        }

        $workflow->load('nodes');

        $trigger = $workflow->nodes->firstWhere('type', 'trigger.chat');

        if (! $trigger) {
            throw $this->notRegistered($request, $token);
        }

        return [$workflow, $trigger];
    }

    private function notRegistered(Request $request, string $token): HttpResponseException
    {
        return new HttpResponseException(response()->json([
            'code' => 404,
            'message' => "The requested webhook \"{$request->method()} $token/chat\" is not registered.",
            'hint' => 'The workflow must be published and active for the production URL to work.',
        ], 404));
    }

    private function supplyNodeIds(Workflow $workflow): array
    {
        $definitions = $this->registry->definitions();

        return $workflow->nodes
            ->filter(function (Node $node) use ($definitions) {
                $definition = $definitions[$node->type] ?? null;

                return $definition
                    && $definition->inputs === []
                    && $definition->outputs !== []
                    && collect($definition->outputs)->every(fn ($handle) => HandleType::isSpecial($handle));
            })
            ->pluck('id')
            ->all();
    }

    private function text(array $config, string $key, string $default): string
    {
        $value = trim((string) ($config[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }

    private function failure(): JsonResponse
    {
        return response()->json(['message' => 'Workflow execution failed.'], 500);
    }
}
