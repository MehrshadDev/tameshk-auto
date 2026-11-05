<?php

namespace App\Tickets\Exceptions;

use RuntimeException;

class TicketException extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
