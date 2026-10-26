<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowGraphRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nodes' => ['present', 'array', 'max:250'],
            'nodes.*.key' => ['required', 'string', 'max:100'],
            'nodes.*.id' => ['nullable', 'integer'],
            'nodes.*.type' => ['required', 'string', 'max:50'],
            'nodes.*.name' => ['required', 'string', 'max:100'],
            'nodes.*.position' => ['required', 'array'],
            'nodes.*.position.x' => ['required', 'numeric'],
            'nodes.*.position.y' => ['required', 'numeric'],
            'nodes.*.config' => ['nullable', 'array'],

            'edges' => ['present', 'array', 'max:500'],
            'edges.*.source' => ['required', 'string', 'max:100'],
            'edges.*.target' => ['required', 'string', 'max:100'],
            'edges.*.sourceHandle' => ['nullable', 'string', 'max:50'],
            'edges.*.targetHandle' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $nodes = $this->input('nodes', []);
            $edges = $this->input('edges', []);

            $triggerCount = collect($nodes)
                ->filter(fn ($node) => str_starts_with($node['type'] ?? '', 'trigger.'))
                ->count();

            if ($triggerCount > 1) {
                $validator->errors()->add('nodes', 'هر ورکفلو حداکثر می‌تواند یک نود شروع‌کننده داشته باشد.');
            }

            $nodeKeys = array_flip(collect($nodes)->pluck('key')->all());

            foreach ($edges as $index => $edge) {
                if (! isset($nodeKeys[$edge['source'] ?? ''])) {
                    $validator->errors()->add("edges.{$index}.source", 'نود مبدا در لیست نودها وجود ندارد.');
                }
                if (! isset($nodeKeys[$edge['target'] ?? ''])) {
                    $validator->errors()->add("edges.{$index}.target", 'نود مقصد در لیست نودها وجود ندارد.');
                }
            }
        });
    }

    protected function passedValidation(): void
    {
        $sanitizedNodes = collect($this->input('nodes', []))->map(function (array $node) {
            $node['name'] = trim(strip_tags((string) ($node['name'] ?? '')));
            return $node;
        })->all();

        $this->merge(['nodes' => $sanitizedNodes]);
    }
}
