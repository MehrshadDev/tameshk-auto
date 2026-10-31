<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class WikipediaToolNode implements NodeContract, ToolContract
{
    private const ALLOWED_LANGS = ['fa', 'en', 'ar', 'tr', 'de', 'fr'];

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.wikipedia',
            name: 'Wikipedia Tool',
            category: NodeCategory::Ai,
            description: 'ابزار جستجو و استخراج دانش و مقالات معتبر از دانشنامه ویکی‌پدیا توسط ایجنت هوش مصنوعی.',
            icon: 'brand:wikipedia',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'wikipedia_search',
                    'required'    => true,
                    'placeholder' => 'wikipedia_search',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'جستجو در دانشنامه ویکی‌پدیا و دریافت متن و خلاصه مقالات علمی، تاریخی، جغرافیایی و عمومی به زبان فارسی یا انگلیسی.',
                    'required' => true,
                ],
                'default_language' => [
                    'type'     => 'select',
                    'label'    => 'زبان پیش‌فرض ویکی‌پدیا',
                    'options'  => [
                        ['value' => 'fa', 'label' => 'فارسی (fa.wikipedia.org)'],
                        ['value' => 'en', 'label' => 'انگلیسی (en.wikipedia.org)'],
                        ['value' => 'ar', 'label' => 'عربی (ar.wikipedia.org)'],
                        ['value' => 'tr', 'label' => 'ترکی (tr.wikipedia.org)'],
                    ],
                    'default'  => 'fa',
                    'required' => true,
                ],
                'limit' => [
                    'type'     => 'number',
                    'label'    => 'تعداد نتایج جستجو',
                    'default'  => 3,
                    'min'      => 1,
                    'max'      => 10,
                    'integer'  => true,
                    'advanced' => true,
                ],
            ],
            color: 'indigo',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'wikipedia_search'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'جستجو و دریافت مقالات از ویکی‌پدیا'));
    }

    public function toolParameters(array $config): array
    {
        return [
            'type'       => 'OBJECT',
            'properties' => [
                'query' => [
                    'type'        => 'STRING',
                    'description' => 'عبارت یا عنوان موضوع مورد نظر برای جستجو در ویکی‌پدیا',
                ],
                'language' => [
                    'type'        => 'STRING',
                    'description' => 'کد دوحرفی زبان ویکی‌پدیا مانند "fa" برای فارسی یا "en" برای انگلیسی (اختیاری)',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $query = trim((string) ($arguments['query'] ?? ''));

        if ($query === '') {
            throw NodeExecutionException::because('عبارت جستجو (query) در ویکی‌پدیا نمی‌تواند خالی باشد.');
        }

        $defaultLang = (string) ($context->config['default_language'] ?? 'fa');
        $lang = strtolower(trim((string) ($arguments['language'] ?? $defaultLang)));

        if (! in_array($lang, self::ALLOWED_LANGS, true)) {
            $lang = $defaultLang;
        }

        $limit = max(1, min(10, (int) ($context->config['limit'] ?? 3)));
        $endpoint = "https://$lang.wikipedia.org/w/api.php";

        try {
            $searchRes = Http::withHeaders([
                'User-Agent' => 'TameshkWorkflowEngine/1.0',
            ])->timeout(20)->get($endpoint, [
                'action'   => 'query',
                'list'     => 'search',
                'srsearch' => $query,
                'srlimit'  => $limit,
                'format'   => 'json',
                'utf8'     => 1,
            ]);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با ویکی‌پدیا: {$e->getMessage()}");
        }

        if ($searchRes->failed()) {
            throw NodeExecutionException::because("ویکی‌پدیا با کد وضعیت {$searchRes->status()} پاسخ داد.");
        }

        $searchItems = (array) data_get($searchRes->json(), 'query.search', []);

        if ($searchItems === []) {
            return [
                'query'    => $query,
                'language' => $lang,
                'count'    => 0,
                'results'  => [],
                'message'  => 'نتیجه‌ای در ویکی‌پدیا یافت نشد.',
            ];
        }

        $pageIds = array_values(array_filter(array_map(fn ($item) => $item['pageid'] ?? null, $searchItems)));

        $extractsByPageId = [];

        if ($pageIds !== []) {
            try {
                $extractRes = Http::withHeaders([
                    'User-Agent' => 'TameshkWorkflowEngine/1.0',
                ])->timeout(20)->get($endpoint, [
                    'action'      => 'query',
                    'prop'        => 'extracts',
                    'exintro'     => 1,
                    'explaintext' => 1,
                    'pageids'     => implode('|', $pageIds),
                    'format'      => 'json',
                    'utf8'        => 1,
                ]);

                if ($extractRes->successful()) {
                    $pages = (array) data_get($extractRes->json(), 'query.pages', []);
                    foreach ($pages as $pid => $pageData) {
                        $extractsByPageId[$pid] = trim((string) ($pageData['extract'] ?? ''));
                    }
                }
            } catch (Throwable) {
            }
        }

        $results = [];

        foreach ($searchItems as $item) {
            $pageId = $item['pageid'] ?? 0;
            $title = (string) ($item['title'] ?? '');
            $snippet = trim(strip_tags((string) ($item['snippet'] ?? '')));
            $extract = $extractsByPageId[$pageId] ?? '';

            $results[] = [
                'title'   => $title,
                'url'     => "https://$lang.wikipedia.org/wiki/" . rawurlencode(str_replace(' ', '_', $title)),
                'summary' => $extract !== '' ? mb_substr($extract, 0, 2500) : $snippet,
            ];
        }

        return [
            'query'    => $query,
            'language' => $lang,
            'count'    => count($results),
            'results'  => $results,
        ];
    }
}
