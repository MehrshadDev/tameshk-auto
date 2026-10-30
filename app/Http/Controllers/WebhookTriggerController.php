<?php

namespace App\Http\Controllers;

use App\Credentials\CredentialResolver;
use App\Jobs\RunWorkflowJob;
use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Nodes\Triggers\WebhookTriggerNode;
use App\Workflow\Runner\WorkflowRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WebhookTriggerController extends Controller
{
    public function __construct(
        private readonly CredentialResolver $credentials,
    ) {}

    public function test(Request $request, string $path): JsonResponse
    {
        $resolved = $this->resolveAndValidate($request, $path, requirePublished: false);

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$workflow, $trigger, $payload] = $resolved;

        if (! Cache::get(WebhookTriggerNode::listeningKey($workflow->id))) {
            return $this->fail(404, 'وبهوک تست در حال گوش دادن نیست. ابتدا در ویرایشگر روی دکمه «گوش دادن به رویداد تستی» کلیک کنید.');
        }

        Cache::put(WebhookTriggerNode::cacheKey($workflow->id), $payload, now()->addMinutes(2));

        return response()->json([
            'message' => (string) ($trigger->config['response_body'] ?? 'Test webhook received'),
            'data'    => $payload,
        ], $this->statusCode($trigger));
    }

    public function production(Request $request, string $path, WorkflowRunner $runner): JsonResponse
    {
        $resolved = $this->resolveAndValidate($request, $path, requirePublished: true);

        if ($resolved instanceof JsonResponse) {
            return $resolved;
        }

        [$workflow, $trigger, $payload] = $resolved;

        $statusCode = $this->statusCode($trigger);

        if (($trigger->config['respond'] ?? 'immediately') === 'last_node') {
            set_time_limit(180);

            try {
                $run = $runner->run($workflow, $payload);

                if ($run->status !== 'success') {
                    return $this->fail(500, $run->error ?? 'Workflow execution failed.');
                }

                return response()->json($run->lastFlowNodeRun($workflow)?->output_data ?? ['ok' => true], $statusCode);
            } catch (Throwable $e) {
                report($e);

                return $this->fail(500, 'Workflow execution failed.');
            }
        }

        RunWorkflowJob::dispatch($workflow->id, $payload);

        $message = trim((string) ($trigger->config['response_body'] ?? 'Workflow was started'));

        return response()->json(['message' => $message !== '' ? $message : 'Workflow was started'], $statusCode);
    }

    private function resolveAndValidate(Request $request, string $rawPath, bool $requirePublished): array|JsonResponse
    {
        $cleanPath = WebhookTriggerNode::normalizePath($rawPath);

        $matchingNodes = Node::query()
            ->where('type', 'trigger.webhook')
            ->whereHas('workflow')
            ->with('workflow')
            ->get()
            ->filter(fn (Node $node) => WebhookTriggerNode::normalizePath($node->config['path'] ?? '') === $cleanPath)
            ->values();

        if ($matchingNodes->count() > 1) {
            return $this->fail(409, 'این آدرس سفارشی وبهوک در بیش از یک ورکفلو انتخاب شده است. لطفاً مسیر سفارشی را تغییر دهید.');
        }

        $trigger = $matchingNodes->first();
        $workflow = $trigger?->workflow;

        if (! $trigger) {
            $workflow = Workflow::query()->where('webhook_token', $cleanPath)->first();
            $trigger = $workflow?->nodes()->where('type', 'trigger.webhook')->first();

            if ($trigger && WebhookTriggerNode::normalizePath($trigger->config['path'] ?? '') !== '') {
                $trigger = null;
            }
        }

        if (! $workflow || ! $trigger) {
            return $this->fail(404, 'وبهوک مورد نظر یافت نشد.');
        }

        if ($requirePublished && (! $workflow->isPublished() || ! $workflow->is_active)) {
            return $this->fail(404, 'این وبهوک فعال نیست. ورکفلو باید منتشر و فعال باشد.');
        }

        $pathError = WebhookTriggerNode::pathError($trigger->config['path'] ?? null, $workflow->id);

        if ($pathError) {
            return $this->fail(409, $pathError);
        }

        $error = $this->validateTriggerRequest($request, $workflow, $trigger);

        return $error ?? [$workflow, $trigger, $this->extractPayload($request, $cleanPath)];
    }

    private function validateTriggerRequest(Request $request, Workflow $workflow, Node $trigger): ?JsonResponse
    {
        $config = $trigger->config ?? [];
        $expectedMethod = strtoupper((string) ($config['http_method'] ?? 'POST'));

        if ($request->method() !== $expectedMethod) {
            return $this->fail(405, "متد درخواست نامعتبر است. این وبهوک فقط متد $expectedMethod را می‌پذیرد.");
        }

        return $this->authorized($request, $workflow, $config) ? null : $this->fail(401, 'Unauthorized.');
    }

    private function authorized(Request $request, Workflow $workflow, array $config): bool
    {
        $mode = (string) ($config['authentication'] ?? 'none');

        if (! in_array($mode, ['bearer', 'basic'], true)) {
            return true;
        }

        $field = $mode === 'bearer' ? 'credential_id' : 'basic_credential_id';
        $credential = $this->credentials->find((int) ($config[$field] ?? 0), $workflow->user_id);

        if ($mode === 'bearer') {
            return $this->same((string) ($credential?->get('token') ?? ''), (string) $request->bearerToken());
        }

        return $this->same((string) ($credential?->get('username') ?? ''), (string) $request->getUser())
            && $this->same((string) ($credential?->get('password') ?? ''), (string) $request->getPassword());
    }

    private function same(string $expected, string $provided): bool
    {
        return $expected !== '' && hash_equals($expected, $provided);
    }

    private function statusCode(Node $trigger): int
    {
        return (int) ($trigger->config['response_code'] ?? 200);
    }

    private function fail(int $status, string $message): JsonResponse
    {
        return response()->json(['code' => $status, 'message' => $message], $status);
    }

    private function extractPayload(Request $request, string $path): array
    {
        $headers = collect($request->headers->all())
            ->map(fn ($values) => is_array($values) ? ($values[0] ?? '') : (string) $values)
            ->all();

        $body = $request->json()->all();
        if (empty($body)) {
            $body = $request->post();
        }

        return [
            'headers'   => $headers,
            'query'     => $request->query(),
            'body'      => $body,
            'method'    => $request->method(),
            'path'      => $path,
            'ip'        => $request->ip(),
            'chatInput' => is_string($body['chatInput'] ?? null)
                ? $body['chatInput']
                : json_encode($body ?: $request->query(), JSON_UNESCAPED_UNICODE),
        ];
    }
}
