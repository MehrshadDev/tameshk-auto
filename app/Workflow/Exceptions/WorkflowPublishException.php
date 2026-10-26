<?php

namespace App\Workflow\Exceptions;

use RuntimeException;

class WorkflowPublishException extends RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode("\n", $errors));
    }

    public static function because(string|array $errors): self
    {
        return new self((array) $errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function summary(): string
    {
        $extra = count($this->errors) - 1;

        if ($extra > 0) {
            return "{$this->errors[0]} (و $extra خطای دیگر)";
        }

        return $this->errors[0] ?? '';
    }
}
