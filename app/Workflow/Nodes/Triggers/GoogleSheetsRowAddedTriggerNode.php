<?php

namespace App\Workflow\Nodes\Triggers;

class GoogleSheetsRowAddedTriggerNode extends GoogleSheetsTriggerNode
{
    protected static function watch(): string
    {
        return 'added';
    }

    protected static function typeName(): string
    {
        return 'trigger.google_sheets_row_added';
    }

    protected static function displayName(): string
    {
        return 'On row added';
    }

    protected static function description(): string
    {
        return 'اجرای ورکفلو با اضافه شدن ردیف جدید به شیت گوگل.';
    }
}
