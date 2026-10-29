<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class HttpBearerCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'http_bearer';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'http_bearer',
            name: 'توکن Bearer',
            description: 'برای API هایی که با Bearer Token کار می‌کنند',
            icon: 'key',
            schema: [
                'token' => [
                    'type'     => 'password',
                    'label'    => 'توکن',
                    'required' => true,
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['token'])) {
            throw InvalidCredentialException::because('توکن الزامی است.');
        }
    }
}
