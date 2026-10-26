<?php

namespace App\Workflow\DTOs;

use App\Workflow\Enums\HandleType;

final class NodeResult
{
    public function __construct(
        public readonly mixed $output,
        public readonly string $handle = HandleType::Main->value,
        public readonly array $meta = [],
    ) {}

    public static function of(
        mixed $output,
        string $handle = HandleType::Main->value,
        array $meta = [],
    ): self {
        return new self($output, $handle, $meta);
    }
}
