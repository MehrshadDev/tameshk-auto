<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\GoogleSheetsClient;

class GoogleSheetsNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.google_sheets',
            name: 'Google Sheets',
            category: NodeCategory::Action,
            description: 'خواندن، افزودن، به‌روزرسانی و پاک‌کردن داده‌ها در اسپردشیت گوگل.',
            icon: 'brand:google-sheets',
            group: 'google-sheets',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'google',
                    'label'           => 'حساب گوگل',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'read', 'label' => 'خواندن ردیف‌ها (Read Rows)'],
                        ['value' => 'append', 'label' => 'افزودن ردیف (Append Row)'],
                        ['value' => 'update', 'label' => 'به‌روزرسانی ردیف (Update Row)'],
                        ['value' => 'clear', 'label' => 'پاک کردن بازه (Clear Range)'],
                    ],
                    'default'  => 'read',
                    'required' => true,
                ],
                'spreadsheet_id' => [
                    'type'        => 'text',
                    'label'       => 'اسپردشیت (شناسه یا URL)',
                    'required'    => true,
                    'placeholder' => 'https://docs.google.com/spreadsheets/d/...',
                ],
                'sheet' => [
                    'type'        => 'text',
                    'label'       => 'نام شیت (Sheet)',
                    'default'     => 'Sheet1',
                    'required'    => true,
                    'placeholder' => 'Sheet1',
                ],

                'read_range' => [
                    'type'        => 'text',
                    'label'       => 'بازه خواندن (A1 Notation)',
                    'default'     => 'A1:Z1000',
                    'placeholder' => 'A1:D100',
                    'depends_on'  => ['operation' => 'read'],
                ],
                'has_header' => [
                    'type'       => 'select',
                    'label'      => 'ردیف اول عنوان ستون‌ها است',
                    'options'    => [
                        ['value' => '1', 'label' => 'بله (خروجی به صورت کلید-مقدار)'],
                        ['value' => '0', 'label' => 'خیر (خروجی به صورت آرایه)'],
                    ],
                    'default'    => '1',
                    'depends_on' => ['operation' => 'read'],
                ],
                'limit' => [
                    'type'       => 'number',
                    'label'      => 'حداکثر تعداد ردیف',
                    'default'    => 100,
                    'min'        => 1,
                    'max'        => 5000,
                    'integer'    => true,
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'read'],
                ],

                'append_range' => [
                    'type'        => 'text',
                    'label'       => 'نقطه شروع (اختیاری)',
                    'placeholder' => 'A1',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'append'],
                ],
                'values' => [
                    'type'        => 'textarea',
                    'label'       => 'مقادیر ردیف (JSON)',
                    'required'    => true,
                    'placeholder' => '["مهرشاد", 30, "تهران"] یا [["مهرشاد", 30], ["سارا", 25]]',
                    'depends_on'  => ['operation' => ['append', 'update']],
                ],
                'value_input_option' => [
                    'type'       => 'select',
                    'label'      => 'نوع ورودی مقادیر',
                    'options'    => [
                        ['value' => 'RAW', 'label' => 'خام (RAW)'],
                        ['value' => 'USER_ENTERED', 'label' => 'تفسیر مثل تایپ در شیت (USER_ENTERED)'],
                    ],
                    'default'    => 'RAW',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['append', 'update']],
                ],

                'update_range' => [
                    'type'        => 'text',
                    'label'       => 'بازه به‌روزرسانی',
                    'required'    => true,
                    'placeholder' => 'A2:D2',
                    'depends_on'  => ['operation' => 'update'],
                ],

                'clear_range' => [
                    'type'        => 'text',
                    'label'       => 'بازه برای پاک‌کردن',
                    'required'    => true,
                    'placeholder' => 'A2:Z1000',
                    'depends_on'  => ['operation' => 'clear'],
                ],
            ],
            presets: [
                [
                    'key'         => 'read_rows',
                    'name'        => 'Read rows',
                    'description' => 'خواندن ردیف‌های یک شیت به صورت آرایه یا کلید-مقدار.',
                    'config'      => ['operation' => 'read'],
                ],
                [
                    'key'         => 'append_row',
                    'name'        => 'Append a row',
                    'description' => 'افزودن یک یا چند ردیف جدید به انتهای شیت.',
                    'config'      => ['operation' => 'append'],
                ],
                [
                    'key'         => 'update_row',
                    'name'        => 'Update a row',
                    'description' => 'جایگزینی مقادیر یک بازه مشخص از شیت.',
                    'config'      => ['operation' => 'update'],
                ],
                [
                    'key'         => 'clear_range',
                    'name'        => 'Clear a range',
                    'description' => 'پاک‌کردن مقادیر یک بازه بدون حذف ساختار شیت.',
                    'config'      => ['operation' => 'clear'],
                ],
            ],
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه حساب گوگل انتخاب نشده است.');
        }

        $operation = (string) ($context->config['operation'] ?? 'read');
        $client = new GoogleSheetsClient($credential);
        $spreadsheetId = GoogleSheetsClient::spreadsheetId((string) ($context->config['spreadsheet_id'] ?? ''));

        return match ($operation) {
            'read'   => $this->handleRead($context, $client, $spreadsheetId),
            'append' => $this->handleAppend($context, $client, $spreadsheetId),
            'update' => $this->handleUpdate($context, $client, $spreadsheetId),
            'clear'  => $this->handleClear($context, $client, $spreadsheetId),
            default  => throw NodeExecutionException::because("عملیات نامعتبر است: $operation"),
        };
    }

    private function handleRead(NodeContext $context, GoogleSheetsClient $client, string $spreadsheetId): NodeResult
    {
        $range = $this->rangeFor($context, 'read_range', 'A1:Z1000');
        $withHeader = ((string) ($context->config['has_header'] ?? '1')) === '1';
        $limit = (int) ($context->config['limit'] ?? 100);

        $context->log("خواندن بازه {$range} از اسپردشیت {$spreadsheetId}");

        $response = $client->getValues($spreadsheetId, $range);
        $values = array_values((array) ($response['values'] ?? []));

        $headers = [];
        $rows = [];

        if ($withHeader) {
            $headerRow = $values[0] ?? [];
            $headers = $this->normalizeRow($headerRow);
            $body = array_slice($values, 1);

            foreach ($body as $row) {
                $rows[] = $this->combineRow($headers, $this->normalizeRow($row));
            }
        } else {
            foreach ($values as $row) {
                $rows[] = $this->normalizeRow($row);
            }
        }

        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        return NodeResult::of([
            'spreadsheet_id' => $spreadsheetId,
            'sheet'          => (string) ($context->config['sheet'] ?? ''),
            'range'          => (string) ($response['range'] ?? $range),
            'headers'        => $headers,
            'rows'           => $rows,
            'values'         => $values,
            'count'          => count($rows),
            'first'          => $rows[0] ?? null,
        ]);
    }

    private function handleAppend(NodeContext $context, GoogleSheetsClient $client, string $spreadsheetId): NodeResult
    {
        $range = $this->rangeFor($context, 'append_range', 'A1');
        $rows = $this->readRows($context);
        $inputOption = (string) ($context->config['value_input_option'] ?? 'RAW');

        $context->log('افزودن ' . count($rows) . ' ردیف به بازه ' . $range);

        $response = $client->appendValues($spreadsheetId, $range, $rows, $inputOption);

        return NodeResult::of([
            'success'        => true,
            'spreadsheet_id' => $spreadsheetId,
            'sheet'          => (string) ($context->config['sheet'] ?? ''),
            'range'          => $range,
            'updated_range'   => (string) data_get($response, 'updates.updatedRange', ''),
            'updated_rows'    => (int) data_get($response, 'updates.updatedRows', count($rows)),
            'updated_columns' => (int) data_get($response, 'updates.updatedColumns', 0),
            'updated_cells'   => (int) data_get($response, 'updates.updatedCells', 0),
            'values'         => $rows,
            'row_count'      => count($rows),
        ]);
    }

    private function handleUpdate(NodeContext $context, GoogleSheetsClient $client, string $spreadsheetId): NodeResult
    {
        $range = $this->rangeFor($context, 'update_range', 'A1');
        $rows = $this->readRows($context);
        $inputOption = (string) ($context->config['value_input_option'] ?? 'RAW');

        $context->log("به‌روزرسانی بازه {$range}");

        $response = $client->updateValues($spreadsheetId, $range, $rows, $inputOption);

        return NodeResult::of([
            'success'         => true,
            'spreadsheet_id'  => $spreadsheetId,
            'sheet'           => (string) ($context->config['sheet'] ?? ''),
            'range'           => $range,
            'updated_range'   => (string) data_get($response, 'updatedRange', ''),
            'updated_rows'    => (int) data_get($response, 'updatedRows', count($rows)),
            'updated_columns' => (int) data_get($response, 'updatedColumns', 0),
            'updated_cells'   => (int) data_get($response, 'updatedCells', 0),
            'values'          => $rows,
            'row_count'       => count($rows),
        ]);
    }

    private function handleClear(NodeContext $context, GoogleSheetsClient $client, string $spreadsheetId): NodeResult
    {
        $range = $this->rangeFor($context, 'clear_range', 'A1:Z1000');

        $context->log("پاک‌کردن بازه {$range}");

        $response = $client->clearValues($spreadsheetId, $range);

        return NodeResult::of([
            'success'         => true,
            'spreadsheet_id'  => $spreadsheetId,
            'sheet'           => (string) ($context->config['sheet'] ?? ''),
            'range'           => $range,
            'cleared_range'   => (string) data_get($response, 'clearedRange', ''),
        ]);
    }

    private function rangeFor(NodeContext $context, string $key, string $default): string
    {
        $sheet = trim((string) ($context->config['sheet'] ?? ''));

        if ($sheet === '') {
            $sheet = 'Sheet1';
        }

        $range = trim((string) ($context->config[$key] ?? ''));

        if ($range === '') {
            $range = $default;
        }

        if (str_contains($range, '!')) {
            return $range;
        }

        $quoted = preg_match('/^[A-Za-z0-9_]+$/', $sheet) === 1
            ? $sheet
            : "'" . str_replace("'", "''", $sheet) . "'";

        return $quoted . '!' . $range;
    }

    private function readRows(NodeContext $context): array
    {
        $raw = trim((string) ($context->config['values'] ?? ''));

        if ($raw === '') {
            throw NodeExecutionException::because('مقادیر ردیف الزامی است.');
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if ($decoded === []) {
                throw NodeExecutionException::because('مقادیر ردیف نمی‌تواند خالی باشد.');
            }

            if (array_is_list($decoded) && is_array($decoded[0] ?? null)) {
                return array_map(fn (array $row) => $this->normalizeRow($row), $decoded);
            }

            if (array_is_list($decoded)) {
                return [$this->normalizeRow($decoded)];
            }

            return [$this->normalizeRow(array_values($decoded))];
        }

        return [$this->normalizeRow(array_map('trim', explode(',', $raw)))];
    }

    private function normalizeRow(array $row): array
    {
        return array_map(function ($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            if (is_bool($value)) {
                return $value ? 'TRUE' : 'FALSE';
            }

            return $value;
        }, array_values($row));
    }

    private function combineRow(array $headers, array $row): array
    {
        $result = [];

        foreach ($headers as $index => $header) {
            $key = trim((string) $header);
            $key = $key !== '' ? $key : 'column_' . ($index + 1);
            $result[$key] = $row[$index] ?? '';
        }

        return $result;
    }
}
