<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class GroqCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'groq';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'groq',
            name: 'Groq',
            description: 'کلید API برای پردازش فوق‌سریع مدل‌های Llama, Mixtral و Qwen روی تراشه‌های LPU',
            icon: 'brand:groq',
            schema: [
                'api_key' => [
                    'type'        => 'password',
                    'label'       => 'API Key',
                    'required'    => true,
                    'placeholder' => 'gsk_...',
                    'help'        => 'از کنسول console.groq.com دریافت کنید.',
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['api_key']) || ! is_string($data['api_key']) || mb_strlen(trim($data['api_key'])) < 10) {
            throw InvalidCredentialException::because('کلید API معتبر الزامی است.');
        }
    }
}
