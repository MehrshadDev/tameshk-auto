<?php

namespace App\Http\Controllers;

use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use App\Workflow\Publishing\WorkflowPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $workflow = Workflow::query()->where('webhook_token', $token)->first();

        abort_if(! $workflow, 404);

        $header = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        abort_unless(hash_equals(WorkflowPublisher::secretFor($workflow), $header), 403);

        if (! $workflow->isPublished() || ! $workflow->is_active) {
            return response()->json(['ok' => true]);
        }

        $update = $request->all();
        $updateId = $update['update_id'] ?? null;

        if (! is_numeric($updateId)) {
            return response()->json(['ok' => true]);
        }

        if (! Cache::add("tg_update_{$workflow->id}_{$updateId}", true, now()->addMinutes(10))) {
            return response()->json(['ok' => true]);
        }

        RunWorkflowJob::dispatch($workflow->id, $update);

        return response()->json(['ok' => true]);
    }
}
