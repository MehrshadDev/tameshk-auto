<?php

namespace App\Workflow\DTOs;

use App\Workflow\Enums\NodeCategory;

final class NodeDefinition
{
    public function __construct(
        public readonly string       $type,
        public readonly string       $name,
        public readonly NodeCategory $category,
        public readonly string       $description = '',
        public readonly ?string      $icon = null,
        public readonly ?string      $group = null,
        public readonly array        $inputs = ['main'],
        public readonly array        $outputs = ['main'],
        public readonly array        $configSchema = [],
        public readonly array        $presets = [],
        public readonly ?string      $color = null,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'category' => $this->category->value,
            'description' => $this->description,
            'icon' => $this->icon,
            'group' => $this->group,
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
            'configSchema' => $this->configSchema,
            'presets' => $this->presets,
            'color' => $this->color,
        ];
    }
}
