<?php

namespace Database\Seeders;

use App\Models\Workflow;
use Illuminate\Database\Seeder;

class DemoGraphSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = Workflow::latest('id')->firstOrFail();

        $workflow->nodes()->delete();

        $start = $workflow->nodes()->create([
            'type' => 'trigger.manual',
            'name' => 'شروع دستی',
            'position_x' => 0,
            'position_y' => 0,
            'config' => [],
        ]);

        $condition = $workflow->nodes()->create([
            'type' => 'logic.condition',
            'name' => 'شرط',
            'position_x' => 320,
            'position_y' => 0,
            'config' => [],
        ]);

        $http = $workflow->nodes()->create([
            'type' => 'action.http',
            'name' => 'درخواست HTTP',
            'position_x' => 680,
            'position_y' => -96,
            'config' => [],
        ]);

        $workflow->edges()->create([
            'source_node_id' => $start->id,
            'target_node_id' => $condition->id,
            'source_handle' => 'main',
        ]);

        $workflow->edges()->create([
            'source_node_id' => $condition->id,
            'target_node_id' => $http->id,
            'source_handle' => 'true',
        ]);

        $this->command->info("ورکفلو $workflow->id: {$workflow->nodes()->count()} نود، {$workflow->edges()->count()} اتصال");
    }
}
