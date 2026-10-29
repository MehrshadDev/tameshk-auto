<?php

namespace App\Workflow\Nodes\Actions\Gemini\Operations;

use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Nodes\Actions\Gemini\GeminiOperationContract;
use App\Workflow\Support\GeminiClient;

final class FileSearchOperation implements GeminiOperationContract
{
    public function handle(NodeContext $context, GeminiClient $client): NodeResult
    {
        $operation = (string)($context->config['operation_file_search'] ?? 'list_stores');

        return match ($operation) {
            'create_store' => (function () use ($context, $client) {
                $name = trim((string)($context->config['store_display_name'] ?? ''));
                if ($name === '') {
                    throw NodeExecutionException::because('نام نمایشی مخزن الزامی است.');
                }
                $context->log("ایجاد مخزن جستجوی فایل: $name");
                $created = $client->createCorpus($name);
                return NodeResult::of([
                    'success' => true,
                    'store_name' => data_get($created, 'name'),
                    'store' => $created,
                ]);
            })(),

            'list_stores' => (function () use ($context, $client) {
                $context->log('دریافت لیست مخازن جستجوی فایل');
                $list = $client->listCorpora(50);
                return NodeResult::of([
                    'count' => count($list),
                    'stores' => $list,
                ]);
            })(),

            'upload_file_to_store' => (function () use ($context, $client) {
                $storeName = trim((string)($context->config['store_name'] ?? ''));
                $title = trim((string)($context->config['document_title'] ?? ''));
                $content = (string)($context->config['file_content'] ?? '');

                if ($storeName === '' || $title === '') {
                    throw NodeExecutionException::because('شناسه مخزن و عنوان سند الزامی هستند.');
                }
                if (trim($content) === '') {
                    throw NodeExecutionException::because('محتوای متنی سند الزامی است.');
                }

                $context->log("افزودن سند به مخزن $storeName");
                $res = $client->uploadFileToCorpus($storeName, $title, $content);

                return NodeResult::of([
                    'success' => true,
                    'store_name' => $storeName,
                    'title' => $title,
                    'result' => $res,
                ]);
            })(),

            'delete_store' => (function () use ($context, $client) {
                $storeName = trim((string)($context->config['store_name'] ?? ''));
                if ($storeName === '') {
                    throw NodeExecutionException::because('شناسه مخزن الزامی است.');
                }
                $context->log("حذف مخزن فایل: $storeName");
                $client->deleteCorpus($storeName);
                return NodeResult::of([
                    'success' => true,
                    'store_name' => $storeName,
                ]);
            })(),

            default => throw NodeExecutionException::because("عملیات نامعتبر مخزن فایل: $operation"),
        };
    }
}
