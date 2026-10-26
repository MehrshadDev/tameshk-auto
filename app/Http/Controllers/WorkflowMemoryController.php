<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Workflow\Support\AgentMemory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowMemoryController
{
    public function clear(Request $request, Workflow $workflow): JsonResponse
    {
        abort_unless($workflow->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        if (filled($data['session_id'] ?? null)) {
            AgentMemory::forget(
                AgentMemory::key($workflow->user_id, $workflow->id, 's:' . $data['session_id'])
            );
        }

        $workflow->nodes()
            ->where('type', 'ai.memory.simple')
            ->get()
            ->each(function ($node) use ($workflow) {
                $custom = trim((string) ($node->config['session_key'] ?? ''));

                if ($custom !== '' && ! str_contains($custom, '{{')) {
                    AgentMemory::forget(
                        AgentMemory::key($workflow->user_id, $workflow->id, 'c:' . $custom)
                    );
                }
            });

        return response()->json(['status' => 'ok']);
    }
}
