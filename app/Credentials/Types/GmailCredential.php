<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class GmailCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'gmail';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'gmail',
            name: 'Gmail',
            description: 'اتصال به حساب گوگل از طریق OAuth2',
            icon: 'brand:gmail',
            schema: [
                'client_id' => [
                    'type'        => 'text',
                    'label'       => 'Client ID',
                    'required'    => true,
                    'placeholder' => 'xxxxxx.apps.googleusercontent.com',
                ],
                'client_secret' => [
                    'type'        => 'password',
                    'label'       => 'Client Secret',
                    'required'    => true,
                    'placeholder' => 'GOCSPX-xxxxxx',
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['client_id']) || ! is_string($data['client_id'])) {
            throw InvalidCredentialException::because('فیلد Client ID الزامی است.');
        }

        if (empty($data['client_secret']) || ! is_string($data['client_secret'])) {
            throw InvalidCredentialException::because('فیلد Client Secret الزامی است.');
        }
    }
}
