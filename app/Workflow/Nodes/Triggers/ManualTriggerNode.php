<?php

namespace App\Workflow\Nodes\Triggers;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;

class ManualTriggerNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'trigger.manual',
            name: 'Manual Trigger',
            category: NodeCategory::Trigger,
            description: 'اجرای ورکفلو به صورت دستی با کلیک روی دکمه یا فراخوانی تستی.',
            icon: 'player-play',
            inputs: [],
            outputs: ['main'],
            configSchema: [],
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->input);
    }
}
