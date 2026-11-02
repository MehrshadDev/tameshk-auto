<?php

namespace App\Subscriptions\Gateways;

use App\Subscriptions\Exceptions\PaymentException;
use Illuminate\Support\Facades\Http;
use Throwable;

final class ZarinpalGateway
{
    private bool $sandbox;
    private string $merchantId;
    private string $currency;

    public function __construct()
    {
        $this->sandbox = (bool) config('saas.zarinpal.sandbox', true);
        $this->merchantId = trim((string) config('saas.zarinpal.merchant_id', ''));
        $this->currency = strtoupper(trim((string) config('saas.zarinpal.currency', 'IRT')));

        if (! in_array($this->currency, ['IRT', 'IRR'], true)) {
            $this->currency = 'IRT';
        }
    }

    public function request(
        int $amountInTomans,
        string $description,
        string $callbackUrl,
        array $metadata = []
    ): array {
        $this->assertConfigured();

        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $this->normalizeAmount($amountInTomans),
            'currency' => $this->currency,
            'description' => $description,
            'callback_url' => $callbackUrl,
        ];

        $cleanMeta = array_filter($metadata, fn ($val) => $val !== null && $val !== '');

        if ($cleanMeta !== []) {
            $payload['metadata'] = $cleanMeta;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(20)
                ->post($this->baseUrl() . '/pg/v4/payment/request.json', $payload);
        } catch (Throwable) {
            throw PaymentException::because('امکان برقراری ارتباط با درگاه پرداخت زرین‌پال وجود ندارد. لطفاً اتصال اینترنت را بررسی کنید.');
        }

        $json = (array) $response->json();
        $code = (int) data_get($json, 'data.code', 0);
        $authority = trim((string) data_get($json, 'data.authority', ''));

        if ($response->failed() || $code !== 100 || $authority === '') {
            $errorCode = (int) data_get($json, 'errors.code', $code);
            throw PaymentException::because($this->translateError($errorCode));
        }

        return [
            'authority' => $authority,
            'redirect_url' => $this->baseUrl() . '/pg/StartPay/' . $authority,
            'raw' => $json,
        ];
    }

    public function verify(int $amountInTomans, string $authority): array
    {
        $this->assertConfigured();

        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $this->normalizeAmount($amountInTomans),
            'authority' => trim($authority),
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(25)
                ->post($this->baseUrl() . '/pg/v4/payment/verify.json', $payload);
        } catch (Throwable) {
            throw PaymentException::because('خطا در استعلام وضعیت پرداخت از زرین‌پال. در صورت کسر وجه، مبلغ ظرف ۷۲ ساعت به حساب شما بازمی‌گردد.');
        }

        $json = (array) $response->json();
        $code = (int) data_get($json, 'data.code', 0);

        if ($response->failed() || ! in_array($code, [100, 101], true)) {
            $errorCode = (int) data_get($json, 'errors.code', $code);
            throw PaymentException::because($this->translateError($errorCode));
        }

        return [
            'code' => $code,
            'already_verified' => $code === 101,
            'ref_id' => (string) data_get($json, 'data.ref_id', ''),
            'card_pan' => (string) data_get($json, 'data.card_pan', ''),
            'card_hash' => (string) data_get($json, 'data.card_hash', ''),
            'raw' => $json,
        ];
    }

    private function baseUrl(): string
    {
        return $this->sandbox
            ? 'https://sandbox.zarinpal.com'
            : 'https://payment.zarinpal.com';
    }

    private function normalizeAmount(int $amountInTomans): int
    {
        return $this->currency === 'IRR'
            ? $amountInTomans * 10
            : $amountInTomans;
    }

    private function assertConfigured(): void
    {
        if (strlen($this->merchantId) !== 36) {
            throw PaymentException::because('کد درگاه (Merchant ID) زرین‌پال به درستی تنظیم نشده است.');
        }
    }

    private function translateError(int $code): string
    {
        return match ($code) {
            -9 => 'اطلاعات ارسالی به درگاه پرداخت نامعتبر است.',
            -10 => 'آی‌پی یا مرچنت کد پذیرنده در زرین‌پال معتبر نیست.',
            -11 => 'مرچنت کد درگاه فعال نیست.',
            -12 => 'تلاش بیش از دفعات مجاز در یک بازه زمانی کوتاه.',
            -15 => 'درگاه پرداخت به حالت تعلیق درآمده است.',
            -16 => 'سطح تایید پذیرنده پایین‌تر از سطح نقره‌ای است.',
            -30 => 'پذیرنده اجازه دسترسی به سرویس تسویه اشتراکی ندارد.',
            -31 => 'حساب بانکی تسویه را در پنل زرین‌پال وارد کنید.',
            -32 => 'مبلغ وارد شده از مبلغ کل تراکنش بیشتر است.',
            -34 => 'مبلغ تراکنش بیش از سقف مجاز است.',
            -40 => 'پارامترهای اضافی ارسالی به درگاه معتبر نیست.',
            -50 => 'مبلغ پرداخت شده با مقدار مبلغ ارسالی در متد تایید متفاوت است.',
            -51 => 'پرداخت ناموفق بوده یا توسط کاربر لغو شده است.',
            -52 => 'خطای غیرمنتظره‌ای در سمت درگاه رخ داده است.',
            -53 => 'پرداخت متعلق به این مرچنت کد نیست.',
            -54 => 'اتوریتی (شناسه پرداخت) نامعتبر است.',
            -55 => 'تراکنش مورد نظر یافت نشد.',
            default => 'خطا در عملیات درگاه پرداخت (کد: ' . $code . ').',
        };
    }
}
