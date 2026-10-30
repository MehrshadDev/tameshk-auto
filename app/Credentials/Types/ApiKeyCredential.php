<?php

namespace App\Credentials\Types;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use App\Credentials\Exceptions\InvalidCredentialException;

abstract class ApiKeyCredential implements CredentialTypeContract
{
    abstract public static function key(): string;

    abstract protected static function displayName(): string;

    abstract protected static function description(): string;

    abstract protected static function icon(): string;

    abstract protected static function help(): string;

    protected static function placeholder(): ?string
    {
        return null;
    }

    protected static function extraSchema(): array
    {
        return [];
    }

    public static function definition(): CredentialDefinition
    {
        $apiKey = array_filter([
            'type'        => 'password',
            'label'       => 'API Key',
            'required'    => true,
            'placeholder' => static::placeholder(),
            'help'        => static::help(),
        ], fn ($value) => $value !== null);

        return new CredentialDefinition(
            key: static::key(),
            name: static::displayName(),
            description: static::description(),
            icon: static::icon(),
            schema: ['api_key' => $apiKey] + static::extraSchema(),
        );
    }

    public function validate(array $data): void
    {
        $apiKey = $data['api_key'] ?? null;

        if (! is_string($apiKey) || mb_strlen(trim($apiKey)) < 10) {
            throw InvalidCredentialException::because('کلید API معتبر الزامی است.');
        }
    }
}
