<?php

namespace App\Workflow\Nodes\Actions\Gemini;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Support\GeminiClient;

interface GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult;
}
