<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

abstract class GoogleOAuthCredential implements CredentialTypeContract
{
    abstract public static function key(): string;

    abstract public static function displayName(): string;

    abstract public static function description(): string;

    abstract public static function icon(): string;

    public static function schema(): array
    {
        return [
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
        ];
    }

    public static function definition(): CredentialDefinition
    {
        return new CredentialDefinition(
            key: static::key(),
            name: static::displayName(),
            description: static::description(),
            icon: static::icon(),
            schema: static::schema(),
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
