<?php

namespace App\Credentials\Registry;

use App\Credentials\Contracts\CredentialTypeContract;
use App\Credentials\DTOs\CredentialDefinition;
use InvalidArgumentException;

final class CredentialRegistry
{
    /** @var array<string, class-string<CredentialTypeContract>> */
    private array $types = [];

    /** @param array<class-string<CredentialTypeContract>> $types */
    public function __construct(array $types = [])
    {
        foreach ($types as $type) {
            $this->register($type);
        }
    }

    public function register(string $typeClass): void
    {
        if (! is_subclass_of($typeClass, CredentialTypeContract::class)) {
            throw new InvalidArgumentException("$typeClass must implement CredentialTypeContract.");
        }

        $key = $typeClass::key();
        $this->types[$key] = $typeClass;
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /** @return class-string<CredentialTypeContract> */
    public function resolve(string $key): string
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown credential type: $key");
        }

        return $this->types[$key];
    }

    public function make(string $key): CredentialTypeContract
    {
        return app($this->resolve($key));
    }

    /** @return array<string, CredentialDefinition> */
    public function definitions(): array
    {
        $result = [];
        foreach ($this->types as $key => $class) {
            $result[$key] = $class::definition();
        }
        return $result;
    }

    /** @return array<int, array> */
    public function definitionsAsArray(): array
    {
        return array_map(fn ($d) => $d->toArray(), array_values($this->definitions()));
    }
}
