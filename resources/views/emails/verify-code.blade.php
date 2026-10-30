@php
    $text = 'margin:0 0 20px;color:#0f172a;font-size:15px;line-height:1.8;text-align:right;';
    $muted = 'margin:0 0 20px;color:#64748b;font-size:13px;line-height:1.8;text-align:right;';
@endphp

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>کد تایید ایمیل</title>
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
                <p dir="rtl" style="{{ $text }}">سلام 👋</p>
                <p dir="rtl" style="{{ $text }}">کد تایید شما جهت ادامه فرآیند به شرح زیر است:</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0;">
                    <tr>
                        <td align="center" style="background:#f3f4f6;border:2px dashed #E21C48;border-radius:12px;padding:20px;text-align:center;">
                            <span dir="ltr" style="display:inline-block;direction:ltr;font-size:36px;font-weight:700;letter-spacing:12px;color:#E21C48;">{{ $code }}</span>
                        </td>
                    </tr>
                </table>
                <p dir="rtl" style="{{ $muted }}">⏳ این کد تا {{ $expiresInMinutes }} دقیقه دیگر اعتبار دارد.</p>
                <p dir="rtl" style="{{ $muted }}margin-bottom:0;">اگر شما این درخواست را نداده‌اید، این ایمیل را نادیده بگیرید.</p>
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
