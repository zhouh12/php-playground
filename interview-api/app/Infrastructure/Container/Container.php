<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use Closure;
use InvalidArgumentException;

/**
 * Simple dependency injection container.
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Bind an interface to a concrete implementation.
     */
    public function bind(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind a singleton (shared instance).
     */
    public function singleton(string $abstract, Closure $concrete): void
    {
        $this->bindings[$abstract] = function (Container $container) use ($abstract, $concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete($container);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * Register an existing instance.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a type from the container.
     *
     * @template T of object
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            throw new InvalidArgumentException("No binding found for: {$abstract}");
        }

        return $this->bindings[$abstract]($this);
    }

    /**
     * Check if a binding exists.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
