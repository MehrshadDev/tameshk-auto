<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

class BasicAuthCredential implements CredentialTypeContract
{
    public static function key(): string
    {
        return 'basic_auth';
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: 'basic_auth',
            name: 'Basic Auth',
            description: 'نام کاربری و رمز عبور',
            icon: 'heroicon-o-user-circle',
            schema: [
                'username' => [
                    'type'     => 'text',
                    'label'    => 'نام کاربری',
                    'required' => true,
                ],
                'password' => [
                    'type'     => 'password',
                    'label'    => 'رمز عبور',
                    'required' => true,
                ],
            ],
        );
    }

    public function validate(array $data): void
    {
        if (empty($data['username']) || empty($data['password'])) {
            throw InvalidCredentialException::because('نام کاربری و رمز عبور الزامی هستند.');
        }
    }
}
