<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;

abstract class BaseChatModelNode implements NodeContract
{
    abstract protected static function typeName(): string;

    abstract protected static function displayName(): string;

    abstract protected static function description(): string;

    abstract protected static function credentialType(): string;

    abstract protected static function credentialLabel(): string;

    abstract protected static function icon(): string;

    abstract protected static function color(): string;

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: static::typeName(),
            name: static::displayName(),
            category: NodeCategory::Ai,
            description: static::description(),
            icon: static::icon(),
            group: 'llm',
            inputs: [],
            outputs: [HandleType::ChatModel->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => static::credentialType(),
                    'label'           => static::credentialLabel(),
                    'required'        => true,
                ],
                'model' => [
                    'type'     => 'model_select',
                    'label'    => 'مدل هوش مصنوعی',
                    'required' => true,
                ],
                'temperature' => [
                    'type'     => 'number',
                    'label'    => 'میزان خلاقیت (Temperature)',
                    'default'  => 0.4,
                    'min'      => 0,
                    'max'      => 2,
                    'advanced' => true,
                ],
            ],
            color: static::color(),
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::ChatModel->value);
    }
}
