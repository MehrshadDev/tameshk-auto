<?php

namespace App\Workflow\Nodes\Logic;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Facades\Cache;

class DelayNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'logic.delay',
            name: 'Delay',
            category: NodeCategory::Logic,
            description: 'ایجاد وقفه و تاخیر در ادامه اجرای ورکفلو به مدت مشخص.',
            icon: 'clock',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'seconds' => [
                    'type'        => 'number',
                    'label'       => 'مدت تاخیر (ثانیه)',
                    'required'    => true,
                    'default'     => 5,
                    'min'         => 0,
                    'max'         => 3600,
                    'integer'     => true,
                    'placeholder' => 'مثلاً 5',
                ],
            ],
            color: 'yellow',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $rawSeconds = $context->config['seconds'] ?? null;

        if (! is_numeric($rawSeconds) || (int) $rawSeconds != $rawSeconds) {
            throw NodeExecutionException::because('مدت زمان تاخیر باید یک عدد صحیح باشد و عدد اعشاری مجاز نیست.');
        }

        $seconds = (int) $rawSeconds;

        if ($seconds < 0) {
            throw NodeExecutionException::because('مدت زمان تاخیر نمی‌تواند منفی باشد.');
        }

        $context->log("تاخیر $seconds ثانیه‌ای");

        if ($seconds > 0) {
            $runId = $context->run->id;

            for ($i = 0; $i < $seconds; $i++) {
                if (Cache::get("cancel_workflow_run_$runId")) {
                    break;
                }

                sleep(1);
            }
        }

        return NodeResult::of($context->input);
    }
}
