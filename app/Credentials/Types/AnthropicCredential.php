<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class AnthropicCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'anthropic';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'anthropic',
            name: 'Anthropic (Claude)',
            description: 'کلید API برای دسترسی به مدل‌های Claude Sonnet, Opus و Haiku',
            icon: 'brand:claude',
            schema: [
                'api_key' => [
                    'type'        => 'password',
                    'label'       => 'API Key',
                    'required'    => true,
                    'placeholder' => 'sk-ant-...',
                    'help'        => 'از کنسول console.anthropic.com دریافت کنید.',
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
