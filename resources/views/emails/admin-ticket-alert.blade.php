@php
    use App\Workflow\Support\DateTimeHelper;

    $text = 'margin:0 0 18px;color:#0f172a;font-size:15px;line-height:1.8;text-align:right;';
    $muted = 'margin:0 0 18px;color:#64748b;font-size:13px;line-height:1.8;text-align:right;';
    $ticketUrl = route('app.tickets.show', ['ticket' => $ticket->public_id]);
    $createdAt = ($ticket->created_at ?? now())->copy()->setTimezone('Asia/Tehran');
    $persianDate = DateTimeHelper::toPersianDate($createdAt, 'yyyy/MM/dd - HH:mm');
@endphp

    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تیکت اولویت‌دار جدید</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;" dir="rtl">

<div dir="rtl" style="direction:rtl;text-align:right;background-color:#f3f4f6;padding:40px 20px;font-family:Vazirmatn,Tahoma,Arial,sans-serif;">
    <table role="presentation" dir="rtl" align="center" width="540" cellpadding="0" cellspacing="0" style="max-width:540px;width:100%;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;direction:rtl;">
        <tr>
            <td align="center" style="background:#E21C48;background-image:linear-gradient(135deg,#E21C48 0%,#b01538 100%);padding:28px 24px;text-align:center;">
                <h1 style="margin:0;color:#ffffff;font-size:19px;font-weight:700;">
                    {{ $isUrgentBilling ? '🚨 تیکت فوری بخش مالی — تمشک' : '🍓 تیکت اولویت‌دار کاربران ویژه — تمشک' }}
                </h1>
            </td>
        </tr>
        <tr>
            <td dir="rtl" align="right" style="padding:30px 24px;direction:rtl;text-align:right;">
                <p dir="rtl" style="{{ $text }}">مدیر گرامی، یک تیکت با اولویت رسیدگی بالا در سیستم ثبت شد:</p>

                <table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;background:#f8fafc;border:1px solid #e2e8f0;border-right:4px solid #E21C48;border-radius:12px;padding:18px;direction:rtl;">
                    <tr>
                        <td dir="rtl" align="right" style="padding-bottom:10px;font-size:15px;font-weight:700;color:#0f172a;text-align:right;">
                            {{ $ticket->subject }}
                        </td>
                    </tr>
                    <tr>
                        <td dir="rtl" align="right" style="font-size:13px;color:#334155;line-height:2;text-align:right;">
                            <div><strong>شماره تیکت:</strong> <span dir="ltr" style="font-family:monospace;">#{{ $ticket->id }}</span></div>
                            <div><strong>کاربر فرستنده:</strong> {{ $ticket->user?->name ?? 'کاربر' }} (<span dir="ltr" style="font-family:monospace;">{{ $ticket->user?->email ?? '—' }}</span>)</div>
                            <div><strong>پلن اشتراک کاربر:</strong> {{ $planName }}</div>
                            <div><strong>بخش:</strong> {{ $ticket->department->label() }}</div>
                            <div><strong>اولویت:</strong> {{ $ticket->priority->label() }}</div>
                            <div><strong>زمان ثبت:</strong> <span dir="ltr" style="font-family:monospace;">{{ $persianDate }}</span></div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:26px 0 10px;">
                    <tr>
                        <td align="center" style="text-align:center;">
                            <a href="{{ $ticketUrl }}" target="_blank" style="display:inline-block;background:#E21C48;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">
                                بررسی و پاسخ به تیکت
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td dir="rtl" align="right" style="padding:18px 24px;background:#f9fafb;border-top:1px solid #e2e8f0;direction:rtl;text-align:right;">
                <p dir="rtl" style="{{ $muted }}margin-bottom:0;font-size:12px;">این اطلاع‌رسانی مخصوص مدیران پشتیبانی تمشک ارسال شده است.</p>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
