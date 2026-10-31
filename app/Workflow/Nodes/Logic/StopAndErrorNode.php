<?php

namespace App\Workflow\Nodes\Logic;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;

class StopAndErrorNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'logic.stop_error',
            name: 'Stop and Error',
            category: NodeCategory::Logic,
            description: 'توقف فوری اجرای ورکفلو یا برگرداندن پیام خطای سفارشی.',
            icon: 'ban',
            inputs: [HandleType::Main->value],
            outputs: [],
            configSchema: [
                'action_type' => [
                    'type'     => 'select',
                    'label'    => 'نوع توقف',
                    'options'  => [
                        ['value' => 'error', 'label' => 'توقف با خطا (Throw Error)'],
                        ['value' => 'stop', 'label' => 'توقف موفق مسیر (Stop Quietly)'],
                    ],
                    'default'  => 'error',
                    'required' => true,
                ],
                'error_message' => [
                    'type'        => 'textarea',
                    'label'       => 'پیام خطا',
                    'default'     => 'اجرای ورکفلو به دلیل خطای منطقی متوقف شد.',
                    'placeholder' => 'مثلاً: موجودی کافی نیست یا اطلاعات کاربر نامعتبر است.',
                    'required'    => true,
                    'depends_on'  => ['action_type' => 'error'],
                ],
            ],
            color: 'rose',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $action = (string) ($context->config['action_type'] ?? 'error');

        if ($action === 'stop') {
            $context->log('توقف برنامه‌ریزی‌شده مسیر');

            return NodeResult::of([
                'stopped' => true,
                'input'   => $context->input,
            ], HandleType::Stopped->value);
        }

        $message = trim((string) ($context->config['error_message'] ?? ''));

        throw NodeExecutionException::because(
            $message !== '' ? $message : 'اجرای ورکفلو توسط نود Stop and Error متوقف شد.'
        );
    }
}
