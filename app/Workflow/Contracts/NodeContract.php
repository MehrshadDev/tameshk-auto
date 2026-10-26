<?php

namespace App\Workflow\Contracts;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;

interface NodeContract
{
    public static function definition(): NodeDefinition;

    /**
     * @throws \App\Workflow\Exceptions\NodeExecutionException
     */
    public function execute(NodeContext $context): NodeResult;
}
