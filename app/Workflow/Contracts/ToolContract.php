<?php

namespace App\Workflow\Contracts;

use App\Workflow\DTOs\NodeContext;

interface ToolContract
{
    public function toolName(array $config): string;

    public function toolDescription(array $config): string;

    public function toolParameters(array $config): array;

    public function executeTool(NodeContext $context, array $arguments): mixed;
}
