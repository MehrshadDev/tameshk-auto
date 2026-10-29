<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class OpenAiCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'openai';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'openai',
            name: 'OpenAI (ChatGPT)',
            description: 'کلید API برای دسترسی به مدل‌های GPT-4o, o3 و سایر مدل‌های OpenAI',
            icon: 'brand:chatgpt',
            schema: [
                'api_key' => [
                    'type'        => 'password',
                    'label'       => 'API Key',
                    'required'    => true,
                    'placeholder' => 'sk-...',
                    'help'        => 'از پلتفرم platform.openai.com دریافت کنید.',
                ],
                'base_url' => [
                    'type'        => 'text',
                    'label'       => 'Base URL (اختیاری)',
                    'required'    => false,
                    'default'     => 'https://api.openai.com/v1',
                    'placeholder' => 'https://api.openai.com/v1',
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
