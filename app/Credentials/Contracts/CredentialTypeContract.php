<?php

namespace App\Credentials\Contracts;

use App\Credentials\DTOs\CredentialDefinition;

interface CredentialTypeContract
{
    public static function key(): string;

    public static function definition(): CredentialDefinition;

    /**
     * @throws \App\Credentials\Exceptions\InvalidCredentialException
     */
    public function validate(array $data): void;
}
