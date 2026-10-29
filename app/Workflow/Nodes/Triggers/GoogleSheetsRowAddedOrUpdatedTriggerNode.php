<?php

namespace App\Workflow\Nodes\Triggers;

class GoogleSheetsRowAddedOrUpdatedTriggerNode extends GoogleSheetsTriggerNode
{
    protected static function watch(): string
    {
        return 'both';
    }

    protected static function typeName(): string
    {
        return 'trigger.google_sheets_row_added_or_updated';
    }

    protected static function displayName(): string
    {
        return 'On row added or updated';
    }

    protected static function description(): string
    {
        return 'اجرای ورکفلو با اضافه شدن یا ویرایش ردیف در شیت گوگل.';
    }
}
