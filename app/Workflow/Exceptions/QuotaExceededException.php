<?php

namespace App\Workflow\Exceptions;

class QuotaExceededException extends NodeExecutionException
{
    public static function monthlyRuns(string $planName, int $limit): self
    {
        $formatted = number_format($limit);

        return new self(
            "سقف اجرای ماهانه پلن «{$planName}» ($formatted اجرا) به پایان رسیده است. برای ادامه اجرا، اشتراک خود را ارتقا دهید."
        );
    }
}
