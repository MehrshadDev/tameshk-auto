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
use App\Workflow\Support\SafeHttpClient;

class WebScraperToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.web_scraper',
            name: 'Web Scraper Tool',
            category: NodeCategory::Ai,
            description: 'ابزار باز کردن آدرس اینترنتی (URL) و استخراج متن تمیز و لینک‌های صفحه وب برای مطالعه توسط ایجنت.',
            icon: 'world-www',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'read_webpage',
                    'required'    => true,
                    'placeholder' => 'read_webpage',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'باز کردن یک آدرس اینترنتی (URL) و استخراج عنوان، توضیحات و محتوای متنی قابل خواندن صفحه وب.',
                    'required' => true,
                ],
                'max_length' => [
                    'type'     => 'number',
                    'label'    => 'حداکثر طول متن استخراج‌شده (کاراکتر)',
                    'default'  => 6000,
                    'min'      => 500,
                    'max'      => 30000,
                    'integer'  => true,
                    'advanced' => true,
                ],
                'include_links' => [
                    'type'     => 'boolean',
                    'label'    => 'استخراج لینک‌های موجود در صفحه',
                    'default'  => false,
                    'advanced' => true,
                ],
            ],
            color: 'teal',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'read_webpage'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'خواندن محتوای متنی یک آدرس وب'));
    }

    public function toolParameters(array $config): array
    {
        return [
            'type'       => 'OBJECT',
            'properties' => [
                'url' => [
                    'type'        => 'STRING',
                    'description' => 'آدرس کامل اینترنتی صفحه وب (شروع با http:// یا https://)',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $url = trim((string) ($arguments['url'] ?? ''));

        if ($url === '') {
            throw NodeExecutionException::because('آدرس صفحه وب (url) الزامی است.');
        }

        $maxLength = max(500, min(30000, (int) ($context->config['max_length'] ?? 6000)));
        $includeLinks = filter_var($context->config['include_links'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $client = new SafeHttpClient();
        $response = $client->execute('GET', $url, [
            'User-Agent' => 'Mozilla/5.0 (compatible; TameshkBot/1.0)',
            'Accept'     => 'text/html,application/xhtml+xml,application/json,text/plain;q=0.9,*/*;q=0.8',
        ], null, 30);

        $rawBody = $response['body'];

        if (is_array($rawBody)) {
            return [
                'url'          => $url,
                'content_type' => $response['content_type'],
                'json'         => $rawBody,
            ];
        }

        $html = (string) $rawBody;

        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch)) {
            $title = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $description = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $html, $descMatch)
            || preg_match('/<meta[^>]+content=["\'](.*?)["\'][^>]+name=["\']description["\']/is', $html, $descMatch)) {
            $description = trim(html_entity_decode($descMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $links = [];
        if ($includeLinks && preg_match_all('/<a\s[^>]*href=["\'](https?:\/\/[^"\'>\s]+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            $seen = [];
            foreach ($matches as $m) {
                $href = trim($m[1]);
                $anchor = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($m[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($href !== '' && ! isset($seen[$href])) {
                    $seen[$href] = true;
                    $links[] = ['text' => mb_substr($anchor, 0, 100), 'url' => $href];
                    if (count($links) >= 25) {
                        break;
                    }
                }
            }
        }

        $cleanHtml = preg_replace('/<(script|style|noscript|svg|iframe|header|footer|nav)\b[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $cleanHtml = preg_replace('/<(br|p|div|li|tr|h[1-6]|section|article)\b[^>]*>/i', "\n", $cleanHtml) ?? $cleanHtml;
        $text = html_entity_decode(strip_tags($cleanHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s*\n+/', "\n\n", $text) ?? $text;
        $text = trim($text);

        $result = [
            'url'         => $url,
            'title'       => $title,
            'description' => $description,
            'content'     => mb_substr($text, 0, $maxLength),
            'truncated'   => mb_strlen($text) > $maxLength,
        ];

        if ($includeLinks) {
            $result['links'] = $links;
        }

        return $result;
    }
}
