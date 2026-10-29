<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiSchema;
use App\Workflow\Nodes\Actions\Gemini\Operations\TextOperation;
use App\Workflow\Nodes\Actions\Gemini\Operations\ImageOperation;
use App\Workflow\Nodes\Actions\Gemini\Operations\AudioOperation;
use App\Workflow\Nodes\Actions\Gemini\Operations\DocumentOperation;
use App\Workflow\Nodes\Actions\Gemini\Operations\FileSearchOperation;
use App\Workflow\Nodes\Actions\Gemini\Operations\MediaFileOperation;
use App\Workflow\Nodes\Actions\Gemini\Operations\VideoOperation;
use App\Workflow\Support\GeminiClient;

class GeminiNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return GeminiSchema::make();
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه Gemini انتخاب نشده است.');
        }

        $client = new GeminiClient($credential);
        $resource = (string) ($context->config['resource'] ?? 'text');

        $operation = match ($resource) {
            'text' => new TextOperation(),
            'image' => new ImageOperation(),
            'audio' => new AudioOperation(),
            'document' => new DocumentOperation(),
            'file_search' => new FileSearchOperation(),
            'media_file' => new MediaFileOperation(),
            'video' => new VideoOperation(),
            default => throw NodeExecutionException::because("بخش انتخابی نامعتبر است: $resource"),
        };

        return $operation->handle($context, $client);
    }
}
