<?php

namespace App\Workflow\Registry;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeDefinition;
use InvalidArgumentException;

final class NodeRegistry
{
    /** @var array<string, class-string<NodeContract>> */
    private array $nodes = [];

    /** @param array<class-string<NodeContract>> $nodeClasses */
    public function __construct(array $nodeClasses = [])
    {
        foreach ($nodeClasses as $class) {
            $this->register($class);
        }
    }

    public function register(string $nodeClass): void
    {
        if (! is_subclass_of($nodeClass, NodeContract::class)) {
            throw new InvalidArgumentException("$nodeClass must implement NodeContract.");
        }

        $type = $nodeClass::definition()->type;
        $this->nodes[$type] = $nodeClass;
    }

    public function has(string $type): bool
    {
        return isset($this->nodes[$type]);
    }

    /** @return class-string<NodeContract> */
    public function resolve(string $type): string
    {
        if (! $this->has($type)) {
            throw new InvalidArgumentException("Unknown node type: $type");
        }

        return $this->nodes[$type];
    }

    public function make(string $type): NodeContract
    {
        return app($this->resolve($type));
    }

    /** @return array<string, NodeDefinition> */
    public function definitions(): array
    {
        $result = [];
        foreach ($this->nodes as $type => $class) {
            $result[$type] = $class::definition();
        }
        return $result;
    }

    /** @return array<int, array> */
    public function definitionsByCategory(): array
    {
        $grouped = [];
        foreach ($this->definitions() as $def) {
            $grouped[$def->category->value][] = $def->toArray();
        }
        return $grouped;
    }
}
