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
use App\Workflow\Support\GoogleSheetsClient;
use App\Workflow\Support\GoogleSheetsConfig;

class GoogleSheetsToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.google_sheets',
            name: 'Google Sheets Tool',
            category: NodeCategory::Ai,
            description: 'ابزار خواندن، جستجو، افزودن و ویرایش ردیف‌ها در Google Sheets توسط ایجنت هوش مصنوعی.',
            icon: 'brand:google-sheets',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: array_merge(
                [
                    'credential_id' => [
                        'type'            => 'credential',
                        'credential_type' => 'google',
                        'label'           => 'حساب گوگل',
                        'required'        => true,
                    ],
                    'tool_name' => [
                        'type'        => 'text',
                        'label'       => 'نام ابزار (انگلیسی)',
                        'default'     => 'google_sheets',
                        'required'    => true,
                        'placeholder' => 'google_sheets',
                    ],
                    'tool_description' => [
                        'type'     => 'textarea',
                        'label'    => 'توضیحات عملکرد ابزار برای مدل',
                        'default'  => 'خواندن اطلاعات، جستجوی ردیف‌ها، افزودن ردیف جدید و به‌روزرسانی ردیف‌ها در اسپردشیت گوگل (Google Sheets).',
                        'required' => true,
                    ],
                    'operation' => [
                        'type'     => 'select',
                        'label'    => 'عملیات مجاز ابزار',
                        'options'  => [
                            ['value' => 'auto', 'label' => 'همه عملیات‌ها (تشخیص خودکار توسط ایجنت)'],
                            ['value' => 'read', 'label' => 'فقط خواندن و جستجوی ردیف‌ها (Read / Search)'],
                            ['value' => 'append', 'label' => 'فقط افزودن ردیف جدید (Append Row)'],
                            ['value' => 'update', 'label' => 'فقط به‌روزرسانی ردیف (Update Row)'],
                            ['value' => 'clear', 'label' => 'فقط پاک کردن بازه (Clear Range)'],
                        ],
                        'default'  => 'auto',
                        'required' => true,
                    ],
                ],
                GoogleSheetsConfig::schema(),
                [
                    'has_header' => [
                        'type'     => 'select',
                        'label'    => 'سطر اول شیت عنوان ستون‌هاست؟',
                        'options'  => [
                            ['value' => '1', 'label' => 'بله (ستون‌های نام‌گذاری‌شده)'],
                            ['value' => '0', 'label' => 'خیر (لیست ساده مقادیر)'],
                        ],
                        'default'  => '1',
                        'advanced' => true,
                    ],
                    'limit' => [
                        'type'     => 'number',
                        'label'    => 'حداکثر تعداد ردیف در هر بار خواندن',
                        'default'  => 50,
                        'min'      => 1,
                        'max'      => 500,
                        'integer'  => true,
                        'advanced' => true,
                    ],
                ],
            ),
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'google_sheets'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'خواندن و نوشتن اطلاعات در Google Sheets'));
    }

    public function toolParameters(array $config): array
    {
        $operation = (string) ($config['operation'] ?? 'auto');
        $properties = [];
        $required = [];

        if ($operation === 'auto') {
            $properties['action'] = [
                'type'        => 'STRING',
                'description' => 'نوع عملیات: "read" (خواندن یا جستجوی ردیف‌ها)، "append" (افزودن ردیف جدید)، "update" (ویرایش یک ردیف)، "clear" (پاک کردن بازه)',
            ];
            $required[] = 'action';
        }

        if (in_array($operation, ['auto', 'read'], true)) {
            $properties['search_query'] = [
                'type'        => 'STRING',
                'description' => 'عبارت متنی اختیاری برای فیلتر و جستجو در بین ردیف‌های شیت (در صورت خالی بودن، ردیف‌ها بدون فیلتر برگردانده می‌شوند).',
            ];
        }

        if (in_array($operation, ['auto', 'append', 'update'], true)) {
            $properties['row_data'] = [
                'type'        => 'STRING',
                'description' => 'داده‌های ردیف به صورت رشته JSON؛ می‌تواند یک آبجکت کلید-مقدار هم‌نام با ستون‌ها مثل {"نام":"علی","تلفن":"09120000000"} یا یک آرایه مثل ["علی","09120000000"] باشد.',
            ];

            if (in_array($operation, ['append', 'update'], true)) {
                $required[] = 'row_data';
            }
        }

        if (in_array($operation, ['auto', 'update', 'clear'], true)) {
            $properties['row_number'] = [
                'type'        => 'INTEGER',
                'description' => 'شماره سطر در شیت (مثلاً 2 برای سطر دوم) جهت ویرایش یا پاک‌سازی.',
            ];
            $properties['range'] = [
                'type'        => 'STRING',
                'description' => 'بازه سلول‌ها در صورت نیاز به تعیین دقیق (مانند A2:D2). اگر row_number داده شود نیازی به این فیلد نیست.',
            ];
        }

        return [
            'type'       => 'OBJECT',
            'properties' => $properties,
            'required'   => $required,
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه حساب گوگل برای ابزار Google Sheets انتخاب نشده است.');
        }

        $client = new GoogleSheetsClient($credential);
        $spreadsheetId = GoogleSheetsConfig::resolveSpreadsheet($context);
        $configuredOp = (string) ($context->config['operation'] ?? 'auto');
        $action = $configuredOp === 'auto'
            ? strtolower(trim((string) ($arguments['action'] ?? 'read')))
            : $configuredOp;

        return match ($action) {
            'read', 'search' => $this->handleRead($context, $client, $spreadsheetId, $arguments),
            'append'         => $this->handleAppend($context, $client, $spreadsheetId, $arguments),
            'update'         => $this->handleUpdate($context, $client, $spreadsheetId, $arguments),
            'clear'          => $this->handleClear($context, $client, $spreadsheetId, $arguments),
            default          => ['error' => "عملیات ناشناخته: {$action}. مقادیر مجاز: read, append, update, clear"],
        };
    }

    private function handleRead(
        NodeContext $context,
        GoogleSheetsClient $client,
        string $spreadsheetId,
        array $arguments,
    ): array {
        $customRange = trim((string) ($arguments['range'] ?? ''));
        $range = $customRange !== ''
            ? $this->qualifyRange($context, $customRange)
            : GoogleSheetsConfig::rangeFor($context, null, 'A1:Z1000');

        $withHeader = ((string) ($context->config['has_header'] ?? '1')) === '1';
        $limit = max(1, min(500, (int) ($context->config['limit'] ?? 50)));
        $searchQuery = trim((string) ($arguments['search_query'] ?? ''));

        $response = $client->getValues($spreadsheetId, $range);
        $values = array_values((array) ($response['values'] ?? []));

        $headers = [];
        $rows = [];

        if ($withHeader) {
            $headers = GoogleSheetsConfig::normalizeRow($values[0] ?? []);

            foreach (array_slice($values, 1, null, true) as $index => $row) {
                $normalized = GoogleSheetsConfig::normalizeRow($row);
                $combined = GoogleSheetsConfig::combineRow($headers, $normalized);
                $combined['_row_number'] = $index + 1;
                $rows[] = $combined;
            }
        } else {
            foreach ($values as $index => $row) {
                $rows[] = [
                    '_row_number' => $index + 1,
                    'values'      => GoogleSheetsConfig::normalizeRow($row),
                ];
            }
        }

        if ($searchQuery !== '') {
            $rows = array_values(array_filter($rows, function (array $row) use ($searchQuery) {
                foreach ($row as $key => $val) {
                    if ($key === '_row_number') {
                        continue;
                    }
                    $str = is_array($val) ? implode(' ', $val) : (string) $val;
                    if (mb_stripos($str, $searchQuery) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $sliced = array_slice($rows, 0, $limit);

        return [
            'spreadsheet_id' => $spreadsheetId,
            'headers'        => $headers,
            'total_matched'  => count($rows),
            'returned_count' => count($sliced),
            'rows'           => $sliced,
        ];
    }

    private function handleAppend(
        NodeContext $context,
        GoogleSheetsClient $client,
        string $spreadsheetId,
        array $arguments,
    ): array {
        $rows = $this->prepareRows($context, $client, $spreadsheetId, $arguments);
        $range = GoogleSheetsConfig::rangeFor($context, null, 'A1');
        $response = $client->appendValues($spreadsheetId, $range, $rows, 'USER_ENTERED');

        return [
            'success'       => true,
            'action'        => 'append',
            'updated_range' => (string) data_get($response, 'updates.updatedRange', ''),
            'updated_rows'  => (int) data_get($response, 'updates.updatedRows', count($rows)),
            'values'        => $rows,
        ];
    }

    private function handleUpdate(
        NodeContext $context,
        GoogleSheetsClient $client,
        string $spreadsheetId,
        array $arguments,
    ): array {
        $targetRange = $this->resolveTargetRange($context, $arguments, false);
        $rows = $this->prepareRows($context, $client, $spreadsheetId, $arguments);
        $response = $client->updateValues($spreadsheetId, $targetRange, $rows, 'USER_ENTERED');

        return [
            'success'       => true,
            'action'        => 'update',
            'updated_range' => (string) data_get($response, 'updatedRange', $targetRange),
            'updated_rows'  => (int) data_get($response, 'updatedRows', count($rows)),
            'values'        => $rows,
        ];
    }

    private function handleClear(
        NodeContext $context,
        GoogleSheetsClient $client,
        string $spreadsheetId,
        array $arguments,
    ): array {
        $targetRange = $this->resolveTargetRange($context, $arguments, true);
        $response = $client->clearValues($spreadsheetId, $targetRange);

        return [
            'success'       => true,
            'action'        => 'clear',
            'cleared_range' => (string) data_get($response, 'clearedRange', $targetRange),
        ];
    }

    private function resolveTargetRange(NodeContext $context, array $arguments, bool $fullRowRange): string
    {
        $rowNumber = (int) ($arguments['row_number'] ?? 0);
        $rawRange = trim((string) ($arguments['range'] ?? ''));

        if ($rawRange === '' && $rowNumber > 0) {
            $rawRange = $fullRowRange ? "A{$rowNumber}:Z{$rowNumber}" : "A{$rowNumber}";
        }

        if ($rawRange === '') {
            throw NodeExecutionException::because('برای این عملیات، تعیین row_number یا range الزامی است.');
        }

        return $this->qualifyRange($context, $rawRange);
    }

    private function qualifyRange(NodeContext $context, string $cellRange): string
    {
        if (str_contains($cellRange, '!')) {
            return $cellRange;
        }

        $base = GoogleSheetsConfig::rangeFor($context, null, 'A1');
        $sheetPrefix = explode('!', $base, 2)[0];

        return "{$sheetPrefix}!{$cellRange}";
    }

    private function prepareRows(
        NodeContext $context,
        GoogleSheetsClient $client,
        string $spreadsheetId,
        array $arguments,
    ): array {
        $raw = $arguments['row_data'] ?? $arguments['values'] ?? null;

        if ($raw === null || $raw === '' || $raw === []) {
            throw NodeExecutionException::because('فیلد row_data برای ثبت یا ویرایش ردیف الزامی است.');
        }

        $parsed = is_string($raw) ? json_decode(trim($raw), true) : $raw;

        if (is_string($raw) && (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed))) {
            return [GoogleSheetsConfig::normalizeRow(array_map('trim', explode(',', $raw)))];
        }

        if (! is_array($parsed) || $parsed === []) {
            throw NodeExecutionException::because('ساختار row_data نامعتبر یا خالی است.');
        }

        if (array_is_list($parsed)) {
            if (is_array($parsed[0] ?? null) && array_is_list($parsed[0])) {
                return array_map(fn (array $r) => GoogleSheetsConfig::normalizeRow($r), $parsed);
            }

            if (is_array($parsed[0] ?? null) && ! array_is_list($parsed[0])) {
                $headers = $this->fetchHeaders($context, $client, $spreadsheetId);
                return array_map(fn (array $item) => $this->mapAssociativeToHeaders($item, $headers), $parsed);
            }

            return [GoogleSheetsConfig::normalizeRow($parsed)];
        }

        $headers = $this->fetchHeaders($context, $client, $spreadsheetId);

        return [$this->mapAssociativeToHeaders($parsed, $headers)];
    }

    private function fetchHeaders(
        NodeContext $context,
        GoogleSheetsClient $client,
        string $spreadsheetId,
    ): array {
        if (((string) ($context->config['has_header'] ?? '1')) !== '1') {
            return [];
        }

        $headerRange = GoogleSheetsConfig::rangeFor($context, null, '1:1');
        $response = $client->getValues($spreadsheetId, $headerRange);
        $firstRow = (array) data_get($response, 'values.0', []);

        return GoogleSheetsConfig::normalizeRow($firstRow);
    }

    private function mapAssociativeToHeaders(array $assoc, array $headers): array
    {
        if ($headers === []) {
            return GoogleSheetsConfig::normalizeRow(array_values($assoc));
        }

        $lowerMap = [];
        foreach ($assoc as $k => $v) {
            $lowerMap[mb_strtolower(trim((string) $k))] = $v;
        }

        $row = [];
        $matchedAny = false;

        foreach ($headers as $header) {
            $cleanHeader = mb_strtolower(trim((string) $header));
            if ($cleanHeader !== '' && array_key_exists($cleanHeader, $lowerMap)) {
                $row[] = $lowerMap[$cleanHeader];
                $matchedAny = true;
            } else {
                $row[] = '';
            }
        }

        if (! $matchedAny) {
            return GoogleSheetsConfig::normalizeRow(array_values($assoc));
        }

        return GoogleSheetsConfig::normalizeRow($row);
    }
}
