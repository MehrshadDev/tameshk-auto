@php
    $text = 'margin:0 0 20px;color:#0f172a;font-size:15px;line-height:1.8;text-align:right;';
    $muted = 'margin:0 0 20px;color:#64748b;font-size:13px;line-height:1.8;text-align:right;';
    $ticketUrl = route('app.tickets.show', ['ticket' => $ticket->public_id]);
@endphp

    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پاسخ جدید به تیکت</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;" dir="rtl">

<div dir="rtl" style="direction:rtl;text-align:right;background-color:#f3f4f6;padding:40px 20px;font-family:Vazirmatn,Tahoma,Arial,sans-serif;">
    <table role="presentation" dir="rtl" align="center" width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;direction:rtl;">
        <tr>
            <td align="center" style="background:#E21C48;background-image:linear-gradient(135deg,#E21C48 0%,#b01538 100%);padding:32px 24px;text-align:center;">
                <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">🍓 تمشک</h1>
            </td>
        </tr>
        <tr>
            <td dir="rtl" align="right" style="padding:32px 24px;direction:rtl;text-align:right;">
                <p dir="rtl" style="{{ $text }}">سلام {{ $ticket->user?->name ?? 'کاربر گرامی' }} 👋</p>
                <p dir="rtl" style="{{ $text }}">پاسخ جدیدی از طرف تیم پشتیبانی تمشک برای تیکت شما ثبت شده است:</p>

                <table role="presentation" dir="rtl" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0;direction:rtl;">
                    <tr>
                        <td dir="rtl" align="right" style="background:#f8fafc;border:1px solid #e2e8f0;border-right:4px solid #E21C48;border-radius:12px;padding:16px 20px;text-align:right;direction:rtl;">
                            <div dir="rtl" style="font-size:12px;color:#64748b;margin-bottom:6px;text-align:right;">
                                تیکت شماره <span dir="ltr" style="font-family:monospace;font-weight:700;color:#0f172a;">#{{ $ticket->id }}</span> • {{ $ticket->department->label() }}
                            </div>
                            <div dir="rtl" style="font-size:15px;font-weight:700;color:#0f172a;line-height:1.6;text-align:right;">
                                {{ $ticket->subject }}
                            </div>
                        </td>
                    </tr>
                </table>

                <p dir="rtl" style="{{ $muted }}">برای مطالعه پاسخ پشتیبانی و ادامه گفتگو، روی دکمه زیر کلیک کنید:</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                    <tr>
                        <td align="center" style="text-align:center;">
                            <a href="{{ $ticketUrl }}" target="_blank" style="display:inline-block;background:#E21C48;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:14px 32px;border-radius:12px;">
                                مشاهده پاسخ تیکت
                            </a>
                        </td>
                    </tr>
                </table>

                <p dir="rtl" style="{{ $muted }}margin-bottom:0;">اگر دکمه بالا برای شما باز نمی‌شود، وارد حساب کاربری خود در وب‌سایت تمشک شوید و به بخش «پشتیبانی و تیکت‌ها» مراجعه کنید.</p>
            </td>
        </tr>
        <tr>
            <td dir="rtl" align="right" style="padding:20px 24px;background:#f9fafb;border-top:1px solid #e2e8f0;direction:rtl;text-align:right;">
                <p dir="rtl" style="margin:0;color:#94a3b8;font-size:12px;line-height:1.6;text-align:right;">این ایمیل به صورت خودکار ارسال شده است. لطفاً به آن پاسخ ندهید.</p>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
