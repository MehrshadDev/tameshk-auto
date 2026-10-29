<?php

namespace App\Workflow\Nodes\Triggers;

class GoogleSheetsRowUpdatedTriggerNode extends GoogleSheetsTriggerNode
{
    protected static function watch(): string
    {
        return 'updated';
    }

    protected static function typeName(): string
    {
        return 'trigger.google_sheets_row_updated';
    }

    protected static function displayName(): string
    {
        return 'On row updated';
    }

    protected static function description(): string
    {
        return 'اجرای ورکفلو هنگام ویرایش یک ردیف در شیت گوگل.';
    }
}
