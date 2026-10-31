<?php

namespace App\Workflow\Nodes\Transform;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use Illuminate\Support\Str;

class CryptoNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'transform.crypto',
            name: 'Crypto & Random',
            category: NodeCategory::Transform,
            description: 'تولید کد تایید تصادفی (OTP)، UUID، توکن امن، هش (SHA256/MD5)، امضای HMAC و تبدیل Base64.',
            icon: 'lock',
            inputs: [HandleType::Main->value],
            outputs: [HandleType::Main->value],
            configSchema: [
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'otp', 'label' => 'تولید کد عددی تصادفی (OTP Code)'],
                        ['value' => 'uuid', 'label' => 'تولید شناسه یکتا (UUID / Token)'],
                        ['value' => 'hash', 'label' => 'تولید هش (MD5 / SHA-256 / SHA-512)'],
                        ['value' => 'hmac', 'label' => 'امضای امنیتی با کلید (HMAC)'],
                        ['value' => 'base64_encode', 'label' => 'کدگذاری Base64 (Encode)'],
                        ['value' => 'base64_decode', 'label' => 'رمزگشایی Base64 (Decode)'],
                    ],
                    'default'  => 'otp',
                    'required' => true,
                ],
                'otp_digits' => [
                    'type'       => 'number',
                    'label'      => 'تعداد ارقام کد (۴ تا ۱۰ رقم)',
                    'default'    => 6,
                    'min'        => 4,
                    'max'        => 10,
                    'integer'    => true,
                    'required'   => true,
                    'depends_on' => ['operation' => 'otp'],
                ],
                'token_type' => [
                    'type'       => 'select',
                    'label'      => 'نوع شناسه یا توکن',
                    'options'    => [
                        ['value' => 'uuid', 'label' => 'UUID v4 استاندارد'],
                        ['value' => 'ulid', 'label' => 'ULID قابل مرتب‌سازی زمانی'],
                        ['value' => 'hex32', 'label' => 'توکن هگزادسیمال ۳۲ کاراکتری'],
                        ['value' => 'alnum32', 'label' => 'رشته تصادفی ۳۲ کاراکتری'],
                    ],
                    'default'    => 'uuid',
                    'depends_on' => ['operation' => 'uuid'],
                ],
                'value' => [
                    'type'        => 'textarea',
                    'label'       => 'متن ورودی',
                    'placeholder' => 'مثلاً {{ input.text }}',
                    'required'    => true,
                    'depends_on'  => ['operation' => ['hash', 'hmac', 'base64_encode', 'base64_decode']],
                ],
                'algorithm' => [
                    'type'       => 'select',
                    'label'      => 'الگوریتم هش',
                    'options'    => [
                        ['value' => 'sha256', 'label' => 'SHA-256'],
                        ['value' => 'sha512', 'label' => 'SHA-512'],
                        ['value' => 'md5', 'label' => 'MD5'],
                        ['value' => 'sha1', 'label' => 'SHA-1'],
                    ],
                    'default'    => 'sha256',
                    'depends_on' => ['operation' => ['hash', 'hmac']],
                ],
                'secret_key' => [
                    'type'        => 'password',
                    'label'       => 'کلید مخفی (Secret Key)',
                    'required'    => true,
                    'depends_on'  => ['operation' => 'hmac'],
                ],
            ],
            color: 'amber',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $operation = (string) ($context->config['operation'] ?? 'otp');

        return match ($operation) {
            'otp'           => $this->handleOtp($context),
            'uuid'          => $this->handleUuid($context),
            'hash'          => $this->handleHash($context),
            'hmac'          => $this->handleHmac($context),
            'base64_encode' => $this->handleBase64Encode($context),
            'base64_decode' => $this->handleBase64Decode($context),
            default         => $this->handleOtp($context),
        };
    }

    private function handleOtp(NodeContext $context): NodeResult
    {
        $digits = max(4, min(10, (int) ($context->config['otp_digits'] ?? 6)));
        $min = (int) pow(10, $digits - 1);
        $max = (int) pow(10, $digits) - 1;
        $code = (string) random_int($min, $max);

        return NodeResult::of([
            'code'   => $code,
            'value'  => $code,
            'text'   => $code,
            'digits' => $digits,
        ]);
    }

    private function handleUuid(NodeContext $context): NodeResult
    {
        $type = (string) ($context->config['token_type'] ?? 'uuid');

        $token = match ($type) {
            'ulid'    => (string) Str::ulid(),
            'hex32'   => bin2hex(random_bytes(16)),
            'alnum32' => Str::random(32),
            default   => (string) Str::uuid(),
        };

        return NodeResult::of([
            'value' => $token,
            'token' => $token,
            'text'  => $token,
            'type'  => $type,
        ]);
    }

    private function handleHash(NodeContext $context): NodeResult
    {
        $value = (string) ($context->config['value'] ?? '');
        $algo = strtolower((string) ($context->config['algorithm'] ?? 'sha256'));

        if (! in_array($algo, ['sha256', 'sha512', 'md5', 'sha1'], true)) {
            $algo = 'sha256';
        }

        $hashed = hash($algo, $value);

        return NodeResult::of([
            'value'     => $hashed,
            'hash'      => $hashed,
            'text'      => $hashed,
            'algorithm' => $algo,
        ]);
    }

    private function handleHmac(NodeContext $context): NodeResult
    {
        $value = (string) ($context->config['value'] ?? '');
        $secret = (string) ($context->config['secret_key'] ?? '');
        $algo = strtolower((string) ($context->config['algorithm'] ?? 'sha256'));

        if ($secret === '') {
            throw NodeExecutionException::because('کلید مخفی (Secret Key) برای تولید HMAC الزامی است.');
        }

        if (! in_array($algo, ['sha256', 'sha512', 'md5', 'sha1'], true)) {
            $algo = 'sha256';
        }

        $signature = hash_hmac($algo, $value, $secret);

        return NodeResult::of([
            'value'     => $signature,
            'signature' => $signature,
            'text'      => $signature,
            'algorithm' => $algo,
        ]);
    }

    private function handleBase64Encode(NodeContext $context): NodeResult
    {
        $value = (string) ($context->config['value'] ?? '');
        $encoded = base64_encode($value);

        return NodeResult::of([
            'value' => $encoded,
            'text'  => $encoded,
        ]);
    }

    private function handleBase64Decode(NodeContext $context): NodeResult
    {
        $value = trim((string) ($context->config['value'] ?? ''));
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            throw NodeExecutionException::because('رشته واردشده یک Base64 معتبر نیست.');
        }

        return NodeResult::of([
            'value' => $decoded,
            'text'  => $decoded,
        ]);
    }
}
