<?php

namespace App\Workflow\Graph;

use App\Models\Edge;
use App\Models\Workflow;
use Illuminate\Support\Facades\DB;
use Throwable;

final class GraphSaver
{
    /**
     * @return array<string, int>
     * @throws Throwable
     */
    public function save(Workflow $workflow, array $nodes, array $edges): array
    {
        return DB::transaction(function () use ($workflow, $nodes, $edges): array {
            Workflow::query()->whereKey($workflow->id)->lockForUpdate()->firstOrFail();

            $existing = $workflow->nodes()->get()->keyBy('id');
            $keep = collect($nodes)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

            $workflow->nodes()->whereNotIn('id', $keep)->delete();

            $ids = [];

            foreach ($nodes as $data) {
                $attributes = [
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'position_x' => $data['position']['x'],
                    'position_y' => $data['position']['y'],
                    'config' => $data['config'],
                ];

                $node = $existing->get((int) ($data['id'] ?? 0));

                if ($node) {
                    $node->update($attributes);
                } else {
                    $node = $workflow->nodes()->create($attributes);
                }

                $ids[$data['key']] = $node->id;
            }

            $workflow->edges()->delete();
            Edge::query()->insert($this->edgeRows($workflow, $edges, $ids));

            $workflow->touch();

            return $ids;
        });
    }

    private function edgeRows(Workflow $workflow, array $edges, array $ids): array
    {
        $now = now();

        return collect($edges)
            ->map(fn (array $edge) => [
                'workflow_id' => $workflow->id,
                'source_node_id' => $ids[$edge['source']],
                'target_node_id' => $ids[$edge['target']],
                'source_handle' => $edge['sourceHandle'],
                'target_handle' => $edge['targetHandle'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->unique(fn (array $row) => implode('|', [
                $row['source_node_id'],
                $row['target_node_id'],
                $row['source_handle'],
                $row['target_handle'],
            ]))
            ->values()
            ->all();
    }
}
