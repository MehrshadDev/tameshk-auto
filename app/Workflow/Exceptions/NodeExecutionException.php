<?php

namespace App\Workflow\Exceptions;

use RuntimeException;

class NodeExecutionException extends RuntimeException
{
    public static function because(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }
}
