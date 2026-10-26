<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkflowGraphRequest;
use App\Models\Edge;
use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\Enums\HandleType;
use App\Workflow\Graph\GraphSaver;
use App\Workflow\Graph\NodeConfigValidator;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use stdClass;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Publishing\WorkflowPublisher;
use App\Workflow\Contracts\PublishableTriggerContract;
use App\Workflow\Publishing\TriggerEndpoints;

class WorkflowGraphController extends Controller
{
    public function __construct(
        private readonly NodeRegistry $registry,
        private readonly NodeConfigValidator $configValidator,
    ) {}

    public function show(Workflow $workflow, TriggerEndpoints $endpoints): JsonResponse
    {
        Gate::authorize('view', $workflow);

        $workflow->load('nodes', 'edges');

        return response()->json([
            'workflow' => $workflow->editorSummary(),
            'nodes' => $workflow->nodes->map(fn (Node $node) => [
                'id'       => $node->id,
                'type'     => $node->type,
                'name'     => $node->name,
                'position' => ['x' => $node->position_x, 'y' => $node->position_y],
                'config'   => $node->config ?: new stdClass(),
            ])->values(),
            'edges' => $workflow->edges->map(fn (Edge $edge) => [
                'id'           => $edge->id,
                'source'       => $edge->source_node_id,
                'target'       => $edge->target_node_id,
                'sourceHandle' => $edge->source_handle ?? 'main',
                'targetHandle' => $edge->target_handle,
            ])->values(),
            'definitions' => collect($this->registry->definitions())
                ->map(fn (NodeDefinition $definition) => $definition->toArray() + [
                        'publishable' => $this->registry->make($definition->type) instanceof PublishableTriggerContract,
                    ])
                ->values(),
            'endpoints' => $endpoints->forWorkflow($workflow),
        ]);
    }

    public function update(UpdateWorkflowGraphRequest $request, Workflow $workflow, GraphSaver $saver, WorkflowPublisher $publisher): JsonResponse
    {
        Gate::authorize('update', $workflow);

        $nodes = $request->validated('nodes');
        $edges = $request->validated('edges');

        $workflow->loadMissing('user');

        if (! $workflow->user) {
            abort(409, 'مالک این ورکفلو دیگر در سیستم موجود نیست.');
        }

        $definitions = $this->registry->definitions();
        $errors = [];
        $typeByKey = collect($nodes)->pluck('type', 'key');
        $nameByKey = collect($nodes)->pluck('name', 'key');

        foreach ($nodes as $index => $nodeData) {
            $name = $nodeData['name'];
            $config = $nodeData['config'] ?? [];

            if (! is_array($config)) {
                $errors["nodes.$index.config"] = "نود «{$name}»: ساختار تنظیمات نامعتبر است.";
                continue;
            }

            if ($nodeData['type'] === 'sticky_note') {
                $content = $config['content'] ?? '';

                if (! is_string($content) || mb_strlen($content) > 10000) {
                    $errors["nodes.$index.config.content"] = "یادداشت «{$name}»: متن نامعتبر یا بیش از حد طولانی است.";
                }

                continue;
            }

            $definition = $definitions[$nodeData['type']] ?? null;

            if (! $definition) {
                $errors["nodes.$index.type"] = "نود «{$name}»: نوع نود نامعتبر است.";
                continue;
            }

            $config = array_intersect_key($config, $definition->configSchema + ['disabled' => true]);
            $nodes[$index]['config'] = $config;

            $fieldErrors = $this->configValidator->errors($definition, $config, collect(), complete: false);

            foreach ($fieldErrors as $field => $message) {
                $label = $definition->configSchema[$field]['label'] ?? $field;
                $errors["nodes.$index.config.$field"] = "نود «{$name}» ← فیلد «{$label}»: $message";
            }
        }

        foreach ($edges as $index => $edge) {
            $sourceDef = $definitions[$typeByKey[$edge['source']] ?? ''] ?? null;
            $targetDef = $definitions[$typeByKey[$edge['target']] ?? ''] ?? null;

            if (! $sourceDef || ! $targetDef) {
                continue;
            }

            $label = "اتصال «{$nameByKey->get($edge['source'])}» ← «{$nameByKey->get($edge['target'])}»";
            $sourceHandle = $edge['sourceHandle'] ?? HandleType::Main->value;
            $targetHandle = $edge['targetHandle'] ?? ($targetDef->inputs[0] ?? HandleType::Main->value);

            if (! in_array($sourceHandle, $sourceDef->outputs, true)) {
                $errors["edges.$index.sourceHandle"] = "$label: این نود چنین خروجی‌ای ندارد.";
            } elseif (! in_array($targetHandle, $targetDef->inputs, true)) {
                $errors["edges.$index.targetHandle"] = "$label: این نود ورودی ندارد یا ورودی انتخاب‌شده نامعتبر است.";
            } elseif (
                HandleType::isSpecial($sourceHandle) !== HandleType::isSpecial($targetHandle)
                || (HandleType::isSpecial($sourceHandle) && $sourceHandle !== $targetHandle)
            ) {
                $errors["edges.$index.targetHandle"] = "$label: نوع اتصال این دو نود با هم سازگار نیست.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $ids = $saver->save($workflow, $nodes, $edges);

        $unpublishedReason = null;

        try {
            $publisher->sync($workflow);
        } catch (WorkflowPublishException $e) {
            $publisher->unpublish($workflow);
            $unpublishedReason = $e->summary();
        }

        return response()->json([
            'ids' => (object) $ids,
            'published' => $workflow->isPublished(),
            'unpublished_reason' => $unpublishedReason,
        ]);
    }
}
