<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Workflow\Exceptions\WorkflowPublishException;
use App\Workflow\Publishing\WorkflowPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WorkflowPublishController extends Controller
{
    public function publish(Workflow $workflow, WorkflowPublisher $publisher): JsonResponse
    {
        Gate::authorize('update', $workflow);

        try {
            $publisher->publish($workflow);
        } catch (WorkflowPublishException $e) {
            throw ValidationException::withMessages(['publish' => $e->errors()]);
        }

        return response()->json([
            'message' => 'ورکفلو منتشر شد و از این پس به‌صورت خودکار اجرا می‌شود.',
            'workflow' => $workflow->editorSummary(),
        ]);
    }

    public function unpublish(Workflow $workflow, WorkflowPublisher $publisher): JsonResponse
    {
        Gate::authorize('update', $workflow);

        $publisher->unpublish($workflow);

        return response()->json([
            'message' => 'انتشار ورکفلو لغو شد.',
            'workflow' => $workflow->editorSummary(),
        ]);
    }
}
