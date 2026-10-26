<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class GeminiCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'gemini';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'gemini',
            name: 'Google Gemini',
            description: 'کلید API برای دسترسی به مدل‌های Gemini',
            icon: 'brand:gemini',
            schema: [
                'api_key' => [
                    'type'     => 'password',
                    'label'    => 'API Key',
                    'required' => true,
                    'help'     => 'از Google AI Studio دریافت کنید',
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['api_key']) || ! is_string($data['api_key'])) {
            throw InvalidCredentialException::because('API Key الزامی است.');
        }

        if (mb_strlen(trim($data['api_key'])) < 10) {
            throw InvalidCredentialException::because('طول کلید API وارد شده نامعتبر است.');
        }
    }
}
