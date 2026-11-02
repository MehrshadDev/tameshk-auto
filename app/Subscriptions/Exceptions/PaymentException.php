<?php

namespace App\Subscriptions\Exceptions;

use RuntimeException;

class PaymentException extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
