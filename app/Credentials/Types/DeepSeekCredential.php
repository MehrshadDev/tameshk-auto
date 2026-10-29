<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class DeepSeekCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'deepseek';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'deepseek',
            name: 'DeepSeek',
            description: 'کلید API برای دسترسی به مدل‌های DeepSeek-V3 و DeepSeek-R1',
            icon: 'brand:deepseek',
            schema: [
                'api_key' => [
                    'type'        => 'password',
                    'label'       => 'API Key',
                    'required'    => true,
                    'placeholder' => 'sk-...',
                    'help'        => 'از پلتفرم platform.deepseek.com دریافت کنید.',
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
