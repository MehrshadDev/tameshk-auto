<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;

class TextTransformNode implements NodeContract
{
    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const ARABIC_DIGITS  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    private const ENGLISH_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.text',
            name: 'Text Operations',
            category: NodeCategory::Transform,
            description: 'جایگزینی متن، استخراج الگو با Regex، تفکیک متن به لیست، پاک‌سازی و تبدیل اعداد فارسی/انگلیسی.',
            icon: 'typography',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'نوع عملیات متنی',
                    'options'  => [
                        ['value' => 'replace', 'label' => 'جایگزینی متن (Find & Replace)'],
                        ['value' => 'extract_regex', 'label' => 'استخراج الگو با Regex (Extract Pattern)'],
                        ['value' => 'split', 'label' => 'تفکیک متن به لیست (Split to Array)'],
                        ['value' => 'clean', 'label' => 'پاک‌سازی و نرمال‌سازی متن (Clean & Normalize)'],
                        ['value' => 'substring', 'label' => 'برش بخشی از متن (Substring)'],
                        ['value' => 'case', 'label' => 'تغییر بزرگ و کوچکی حروف (Change Case)'],
                    ],
                    'default'  => 'replace',
                    'required' => true,
                ],
                'text' => [
                    'type'        => 'textarea',
                    'label'       => 'متن ورودی',
                    'default'     => '{{ input.text }}',
                    'placeholder' => 'مثلاً {{ input.text }} یا {{ input.response }}',
                    'required'    => true,
                ],
                'search_value' => [
                    'type'        => 'text',
                    'label'       => 'متن یا الگوی جستجو (Search / Pattern)',
                    'placeholder' => 'عبارت مورد نظر یا الگوی Regex',
                    'required'    => true,
                    'depends_on'  => ['operation' => ['replace', 'extract_regex']],
                ],
                'replace_value' => [
                    'type'        => 'text',
                    'label'       => 'مقدار جایگزین (Replace With)',
                    'default'     => '',
                    'placeholder' => 'متن جدید (برای حذف کامل خالی بگذارید)',
                    'depends_on'  => ['operation' => 'replace'],
                ],
                'use_regex' => [
                    'type'       => 'boolean',
                    'label'      => 'استفاده از عبارت باقاعده (Regular Expression)',
                    'default'    => false,
                    'depends_on' => ['operation' => 'replace'],
                ],
                'delimiter' => [
                    'type'        => 'text',
                    'label'       => 'جداکننده (Delimiter)',
                    'default'     => ',',
                    'placeholder' => 'مثلاً کاما (,) یا \n برای خط جدید',
                    'required'    => true,
                    'depends_on'  => ['operation' => 'split'],
                ],
                'strip_html' => [
                    'type'       => 'boolean',
                    'label'      => 'حذف تگ‌های HTML',
                    'default'    => true,
                    'depends_on' => ['operation' => 'clean'],
                ],
                'trim_whitespace' => [
                    'type'       => 'boolean',
                    'label'      => 'حذف فاصله‌ها و خطوط خالی اضافی',
                    'default'    => true,
                    'depends_on' => ['operation' => 'clean'],
                ],
                'digits_mode' => [
                    'type'       => 'select',
                    'label'      => 'تبدیل ارقام (اعداد)',
                    'options'    => [
                        ['value' => 'none', 'label' => 'بدون تغییر'],
                        ['value' => 'to_en', 'label' => 'تبدیل اعداد فارسی/عربی به انگلیسی (۱۲۳ ← 123)'],
                        ['value' => 'to_fa', 'label' => 'تبدیل اعداد انگلیسی به فارسی (123 ← ۱۲۳)'],
                    ],
                    'default'    => 'to_en',
                    'depends_on' => ['operation' => 'clean'],
                ],
                'start_index' => [
                    'type'       => 'number',
                    'label'      => 'شروع از کاراکتر (Start Index)',
                    'default'    => 0,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'substring'],
                ],
                'length' => [
                    'type'       => 'number',
                    'label'      => 'طول برش (Length)',
                    'default'    => 100,
                    'min'        => 1,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'substring'],
                ],
                'case_type' => [
                    'type'       => 'select',
                    'label'      => 'حالت حروف',
                    'options'    => [
                        ['value' => 'lower', 'label' => 'حروف کوچک (lowercase)'],
                        ['value' => 'upper', 'label' => 'حروف بزرگ (UPPERCASE)'],
                        ['value' => 'title', 'label' => 'حرف اول کلمات بزرگ (Title Case)'],
                    ],
                    'default'    => 'lower',
                    'depends_on' => ['operation' => 'case'],
                ],
            ],
            color: 'indigo',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $operation = (string) ($context->config['operation'] ?? 'replace');
        $text = (string) ($context->config['text'] ?? '');

        return match ($operation) {
            'replace'       => $this->handleReplace($context, $text),
            'extract_regex' => $this->handleExtractRegex($context, $text),
            'split'         => $this->handleSplit($context, $text),
            'clean'         => $this->handleClean($context, $text),
            'substring'     => $this->handleSubstring($context, $text),
            'case'          => $this->handleCase($context, $text),
            default         => $this->textOut($text),
        };
    }

    private function handleReplace(NodeContext $context, string $text): NodeResult
    {
        $search = (string) ($context->config['search_value'] ?? '');
        $replace = (string) ($context->config['replace_value'] ?? '');
        $useRegex = filter_var($context->config['use_regex'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($search === '') {
            return $this->textOut($text);
        }

        if ($useRegex) {
            $pattern = $this->delimitRegex($search);
            $result = @preg_replace($pattern, $replace, $text);
            if ($result === null) {
                throw NodeExecutionException::because('الگوی Regex واردشده معتبر نیست.');
            }
            return $this->textOut($result);
        }

        return $this->textOut(str_replace($search, $replace, $text));
    }

    private function handleExtractRegex(NodeContext $context, string $text): NodeResult
    {
        $rawPattern = trim((string) ($context->config['search_value'] ?? ''));

        if ($rawPattern === '') {
            throw NodeExecutionException::because('الگوی Regex برای استخراج الزامی است.');
        }

        $pattern = $this->delimitRegex($rawPattern);
        $ok = @preg_match_all($pattern, $text, $matches);

        if ($ok === false) {
            throw NodeExecutionException::because('الگوی Regex واردشده معتبر نیست.');
        }

        $fullMatches = $matches[0] ?? [];
        $groups = isset($matches[1]) && $matches[1] !== [] ? $matches[1] : $fullMatches;

        return NodeResult::of([
            'matched' => $fullMatches !== [],
            'text'    => (string) ($groups[0] ?? ''),
            'first'   => $groups[0] ?? null,
            'matches' => array_values($groups),
            'count'   => count($groups),
        ]);
    }

    private function handleSplit(NodeContext $context, string $text): NodeResult
    {
        $delimiter = (string) ($context->config['delimiter'] ?? ',');
        if ($delimiter === '\n') {
            $delimiter = "\n";
        }

        $parts = $delimiter !== '' ? explode($delimiter, $text) : [$text];
        $items = array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));

        return NodeResult::of([
            'items' => $items,
            'rows'  => $items,
            'count' => count($items),
            'first' => $items[0] ?? null,
            'last'  => $items !== [] ? $items[count($items) - 1] : null,
        ]);
    }

    private function handleClean(NodeContext $context, string $text): NodeResult
    {
        $stripHtml = filter_var($context->config['strip_html'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $trimSpace = filter_var($context->config['trim_whitespace'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $digitsMode = (string) ($context->config['digits_mode'] ?? 'to_en');

        $result = $text;

        if ($stripHtml) {
            $result = html_entity_decode(strip_tags($result), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($digitsMode === 'to_en') {
            $result = str_replace(self::PERSIAN_DIGITS, self::ENGLISH_DIGITS, $result);
            $result = str_replace(self::ARABIC_DIGITS, self::ENGLISH_DIGITS, $result);
        } elseif ($digitsMode === 'to_fa') {
            $result = str_replace(self::ARABIC_DIGITS, self::PERSIAN_DIGITS, $result);
            $result = str_replace(self::ENGLISH_DIGITS, self::PERSIAN_DIGITS, $result);
        }

        if ($trimSpace) {
            $result = str_replace("\u{200C}", ' ', $result);
            $result = preg_replace('/[ \t]+/u', ' ', $result) ?? $result;
            $result = preg_replace('/\n\s*\n+/u', "\n\n", $result) ?? $result;
            $result = trim($result);
        }

        return $this->textOut($result);
    }

    private function handleSubstring(NodeContext $context, string $text): NodeResult
    {
        $start = (int) ($context->config['start_index'] ?? 0);
        $length = max(1, (int) ($context->config['length'] ?? 100));

        return $this->textOut(mb_substr($text, $start, $length));
    }

    private function handleCase(NodeContext $context, string $text): NodeResult
    {
        $caseType = (string) ($context->config['case_type'] ?? 'lower');

        $converted = match ($caseType) {
            'upper' => mb_strtoupper($text),
            'title' => mb_convert_case($text, MB_CASE_TITLE, 'UTF-8'),
            default => mb_strtolower($text),
        };

        return $this->textOut($converted);
    }

    private function delimitRegex(string $pattern): string
    {
        return preg_match('/^[\/#~].*[\/#~][imsxADSUXJu]*$/s', $pattern)
            ? $pattern
            : '/' . str_replace('/', '\/', $pattern) . '/u';
    }

    private function textOut(string $result): NodeResult
    {
        return NodeResult::of([
            'text'     => $result,
            'response' => $result,
            'length'   => mb_strlen($result),
        ]);
    }
}
