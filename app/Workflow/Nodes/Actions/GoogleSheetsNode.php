<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\GoogleSheetsClient;
use App\Workflow\Support\GoogleSheetsConfig;

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
            configSchema: array_merge(
                [
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
                            ['value' => 'append', 'label' => 'افزودن ردیف (Append Row)'],
                            ['value' => 'read', 'label' => 'خواندن ردیف‌ها (Get Rows)'],
                            ['value' => 'update', 'label' => 'به‌روزرسانی ردیف (Update Row)'],
                            ['value' => 'clear', 'label' => 'پاک کردن بازه (Clear)'],
                        ],
                        'default'  => 'append',
                        'required' => true,
                    ],
                ],
                GoogleSheetsConfig::schema(),
                [
                    'values' => [
                        'type'        => 'textarea',
                        'label'       => 'مقادیر ردیف (JSON یا جداشده با کاما)',
                        'required'    => true,
                        'placeholder' => '["مهرشاد حسن خانی", "09120000000", "تهران"]',
                        'help'        => 'یک آرایه JSON یا مقادیر جداشده با کاما وارد کنید؛ می‌توانید از {{ input.field }} استفاده کنید.',
                        'depends_on'  => ['operation' => ['append', 'update']],
                    ],
                    'update_range' => [
                        'type'        => 'text',
                        'label'       => 'بازه سلول‌ها (Range)',
                        'required'    => true,
                        'placeholder' => 'A2:D2',
                        'depends_on'  => ['operation' => 'update'],
                    ],
                    'clear_range' => [
                        'type'        => 'text',
                        'label'       => 'بازه پاک کردن (Range)',
                        'required'    => true,
                        'default'     => 'A2:Z1000',
                        'placeholder' => 'A2:Z1000',
                        'depends_on'  => ['operation' => 'clear'],
                    ],
                    'read_range' => [
                        'type'        => 'text',
                        'label'       => 'بازه خواندن (Range)',
                        'default'     => 'A1:Z1000',
                        'placeholder' => 'A1:Z1000',
                        'advanced'    => true,
                        'depends_on'  => ['operation' => 'read'],
                    ],
                    'has_header' => [
                        'type'     => 'select',
                        'label'    => 'فرمت خروجی سطرها',
                        'options'  => [
                            ['value' => '1', 'label' => 'ستون‌های نام‌گذاری‌شده (بر اساس سطر اول شیت)'],
                            ['value' => '0', 'label' => 'لیست ساده مقادیر (بدون عنوان ستون)'],
                        ],
                        'default'  => '1',
                        'advanced' => true,
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
                    'value_input_option' => [
                        'type'       => 'select',
                        'label'      => 'نحوه ثبت مقادیر',
                        'options'    => [
                            ['value' => 'USER_ENTERED', 'label' => 'هوشمند و فرمول‌پذیر (USER_ENTERED)'],
                            ['value' => 'RAW', 'label' => 'متن خام بدون پردازش (RAW)'],
                        ],
                        'default'    => 'USER_ENTERED',
                        'advanced'   => true,
                        'depends_on' => ['operation' => ['append', 'update']],
                    ],
                ],
            ),
            presets: [
                [
                    'key'         => 'append_row',
                    'name'        => 'Append Row',
                    'description' => 'افزودن یک یا چند ردیف جدید به انتهای شیت.',
                    'config'      => ['operation' => 'append'],
                ],
                [
                    'key'         => 'get_rows',
                    'name'        => 'Get Rows',
                    'description' => 'خواندن ردیف‌های شیت به صورت آرایه یا کلید-مقدار.',
                    'config'      => ['operation' => 'read'],
                ],
                [
                    'key'         => 'update_row',
                    'name'        => 'Update Row',
                    'description' => 'جایگزینی مقادیر یک بازه مشخص از شیت.',
                    'config'      => ['operation' => 'update'],
                ],
                [
                    'key'         => 'clear_range',
                    'name'        => 'Clear',
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

        $operation = (string) ($context->config['operation'] ?? 'append');
        $client = new GoogleSheetsClient($credential);
        $spreadsheetId = GoogleSheetsConfig::resolveSpreadsheet($context);

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
        $range = GoogleSheetsConfig::rangeFor($context, 'read_range', 'A1:Z1000');
        $withHeader = ((string) ($context->config['has_header'] ?? '1')) === '1';
        $limit = (int) ($context->config['limit'] ?? 100);

        $context->log("خواندن بازه {$range}");

        $response = $client->getValues($spreadsheetId, $range);
        $values = array_values((array) ($response['values'] ?? []));

        $headers = [];
        $rows = [];

        if ($withHeader) {
            $headers = GoogleSheetsConfig::normalizeRow($values[0] ?? []);

            foreach (array_slice($values, 1) as $row) {
                $rows[] = GoogleSheetsConfig::combineRow($headers, GoogleSheetsConfig::normalizeRow($row));
            }
        } else {
            foreach ($values as $row) {
                $rows[] = GoogleSheetsConfig::normalizeRow($row);
            }
        }

        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        return NodeResult::of([
            'spreadsheet_id' => $spreadsheetId,
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
        $range = GoogleSheetsConfig::rangeFor($context, null, 'A1');
        $rows = $this->readRows($context);
        $inputOption = (string) ($context->config['value_input_option'] ?? 'USER_ENTERED');

        $context->log('افزودن ' . count($rows) . ' ردیف به بازه ' . $range);

        $response = $client->appendValues($spreadsheetId, $range, $rows, $inputOption);

        return NodeResult::of([
            'success'         => true,
            'spreadsheet_id'  => $spreadsheetId,
            'range'           => $range,
            'updated_range'   => (string) data_get($response, 'updates.updatedRange', ''),
            'updated_rows'    => (int) data_get($response, 'updates.updatedRows', count($rows)),
            'updated_columns' => (int) data_get($response, 'updates.updatedColumns', 0),
            'updated_cells'   => (int) data_get($response, 'updates.updatedCells', 0),
            'values'          => $rows,
            'row_count'       => count($rows),
        ]);
    }

    private function handleUpdate(NodeContext $context, GoogleSheetsClient $client, string $spreadsheetId): NodeResult
    {
        $range = GoogleSheetsConfig::rangeFor($context, 'update_range', 'A1');
        $rows = $this->readRows($context);
        $inputOption = (string) ($context->config['value_input_option'] ?? 'USER_ENTERED');

        $context->log("به‌روزرسانی بازه {$range}");

        $response = $client->updateValues($spreadsheetId, $range, $rows, $inputOption);

        return NodeResult::of([
            'success'         => true,
            'spreadsheet_id'  => $spreadsheetId,
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
        $range = GoogleSheetsConfig::rangeFor($context, 'clear_range', 'A2:Z1000');

        $context->log("پاک‌کردن بازه {$range}");

        $response = $client->clearValues($spreadsheetId, $range);

        return NodeResult::of([
            'success'        => true,
            'spreadsheet_id' => $spreadsheetId,
            'range'          => $range,
            'cleared_range'  => (string) data_get($response, 'clearedRange', ''),
        ]);
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
                return array_map(fn (array $row) => GoogleSheetsConfig::normalizeRow($row), $decoded);
            }

            if (array_is_list($decoded)) {
                return [GoogleSheetsConfig::normalizeRow($decoded)];
            }

            return [GoogleSheetsConfig::normalizeRow(array_values($decoded))];
        }

        return [GoogleSheetsConfig::normalizeRow(array_map('trim', explode(',', $raw)))];
    }
}
