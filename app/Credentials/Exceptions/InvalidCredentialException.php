<?php

namespace App\Credentials\Exceptions;

use RuntimeException;

class InvalidCredentialException extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
