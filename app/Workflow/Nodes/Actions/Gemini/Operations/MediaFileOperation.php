<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class MediaFileOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $source = trim((string)($context->config['media_source'] ?? ''));
        $mime = trim((string)($context->config['media_mime_type'] ?? 'image/jpeg'));
        $name = trim((string)($context->config['media_display_name'] ?? ''));

        if ($source === '') {
            throw NodeExecutionException::because('منبع مدیا (URL یا Base64) الزامی است.');
        }

        $context->log("آپلود مدیا در Gemini Files API ($mime)");

        $binary = str_starts_with($source, 'http://') || str_starts_with($source, 'https://')
            ? base64_decode($client->downloadUrl($source))
            : base64_decode(preg_replace('/^data:.*?;base64,/', '', $source));

        $uploaded = $client->uploadMedia($binary, $mime, $name ?: null);

        return NodeResult::of([
            'success' => true,
            'file_name' => data_get($uploaded, 'name'),
            'file_uri' => data_get($uploaded, 'uri'),
            'mime_type' => data_get($uploaded, 'mimeType'),
            'size' => data_get($uploaded, 'sizeBytes'),
            'file' => $uploaded,
        ]);
    }
}
