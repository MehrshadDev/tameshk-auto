<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class OpenRouterCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'openrouter';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'openrouter',
            name: 'OpenRouter',
            description: 'دسترسی یکپارچه به بیش از ۲۰۰ مدل هوش مصنوعی (GPT, Claude, Gemini, Llama و ...)',
            icon: 'brand:openrouter',
            schema: [
                'api_key' => [
                    'type'        => 'password',
                    'label'       => 'API Key',
                    'required'    => true,
                    'placeholder' => 'sk-or-v1-...',
                    'help'        => 'از سایت openrouter.ai دریافت کنید.',
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
