<?php

namespace App\Credentials\DTOs;

final class CredentialDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description = '',
        public readonly ?string $icon = null,
        public readonly array $schema = [],
    ) {}

    public function toArray(): array
    {
        return [
            'key'         => $this->key,
            'name'        => $this->name,
            'description' => $this->description,
            'icon'        => $this->icon,
            'schema'      => $this->schema,
        ];
    }
}
