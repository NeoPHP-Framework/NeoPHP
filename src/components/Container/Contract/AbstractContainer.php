<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container\Contract;

use Closure;
use NeoPHP\Component\Container\Exception\ContainerException;
use NeoPHP\Component\Container\Exception\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

abstract class AbstractContainer implements ContainerInterface
{
    protected array $bindings = [];

    protected array $instances = [];

    protected array $aliases = [];

    private array $building = [];

    public function bind(string $id, mixed $concrete = null, bool $shared = false): static
    {
        unset($this->instances[$id], $this->aliases[$id]);
        $this->bindings[$id] = ['concrete' => $concrete ?? $id, 'shared' => $shared];

        return $this;
    }

    public function singleton(string $id, mixed $concrete = null): static
    {
        return $this->bind($id, $concrete, true);
    }

    public function instance(string $id, mixed $value): static
    {
        unset($this->aliases[$id]);
        $this->instances[$id] = $value;

        return $this;
    }

    public function alias(string $alias, string $id): static
    {
        if ($alias === $id) {
            throw new ContainerException(sprintf('"%s" cannot be aliased to itself.', $id));
        }

        $this->aliases[$alias] = $id;

        return $this;
    }

    public function bound(string $id): bool
    {
        $id = $this->resolveAlias($id);

        return isset($this->bindings[$id]) || array_key_exists($id, $this->instances);
    }

    public function has(string $id): bool
    {
        if ($this->bound($id)) {
            return true;
        }

        $id = $this->resolveAlias($id);

        return class_exists($id) && (new ReflectionClass($id))->isInstantiable();
    }

    public function get(string $id): mixed
    {
        return $this->resolve($id, [], false);
    }

    public function make(string $id, array $parameters = []): mixed
    {
        return $this->resolve($id, $parameters, true);
    }

    public function call(callable|array|string $callable, array $parameters = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable, 2);
        }

        if (is_string($callable) && class_exists($callable)) {
            $callable = [$callable, '__invoke'];
        }

        try {
            if (is_array($callable)) {
                [$target, $method] = $callable;

                if (is_string($target)) {
                    $reflection = new ReflectionMethod($target, $method);
                    $target = $reflection->isStatic() ? null : $this->get($target);
                } else {
                    $reflection = new ReflectionMethod($target, $method);
                }

                return $reflection->invokeArgs($target, $this->resolveArguments($reflection, $parameters));
            }

            if ($callable instanceof Closure || is_string($callable)) {
                $reflection = new ReflectionFunction($callable);

                return $reflection->invokeArgs($this->resolveArguments($reflection, $parameters));
            }

            if (is_object($callable) && method_exists($callable, '__invoke')) {
                $reflection = new ReflectionMethod($callable, '__invoke');

                return $reflection->invokeArgs($callable, $this->resolveArguments($reflection, $parameters));
            }
        } catch (ReflectionException $exception) {
            throw new ContainerException($exception->getMessage(), 0, $exception);
        }

        throw new ContainerException('The given value is not a valid callable.');
    }

    public function resolveArguments(ReflectionFunctionAbstract $function, array $parameters = []): array
    {
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                if ($parameter->isVariadic() && is_array($parameters[$name])) {
                    array_push($arguments, ...array_values($parameters[$name]));
                    break;
                }

                $arguments[] = $parameters[$name];
                continue;
            }

            if ($parameter->isVariadic()) {
                break;
            }

            $arguments[] = $this->resolveParameter($parameter, $parameters, $function);
        }

        return $arguments;
    }

    protected function resolveParameter(ReflectionParameter $parameter, array $parameters, ReflectionFunctionAbstract $function): mixed
    {
        foreach ($this->classTypesOf($parameter) as $class) {
            if (array_key_exists($class, $parameters)) {
                return $parameters[$class];
            }

            if ($this->has($class)) {
                try {
                    return $this->get($class);
                } catch (NotFoundException $exception) {
                    if (!$parameter->isOptional() && !$parameter->allowsNull()) {
                        throw $exception;
                    }
                }
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull() && $parameter->hasType()) {
            return null;
        }

        $owner = $function instanceof ReflectionMethod
            ? $function->getDeclaringClass()->getName() . '::' . $function->getName()
            : $function->getName();

        throw new ContainerException(sprintf(
            'Unable to resolve parameter "$%s" of "%s()"%s.',
            $parameter->getName(),
            $owner,
            $this->classTypesOf($parameter) === [] ? ': no class type-hint and no default value' : ': no entry found for its type',
        ));
    }

    protected function resolve(string $id, array $parameters, bool $forceNew): mixed
    {
        $id = $this->resolveAlias($id);

        if (!$forceNew && array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        $binding = $this->bindings[$id] ?? null;

        if ($binding === null && !class_exists($id)) {
            throw NotFoundException::forId($id);
        }

        $object = $this->build($binding['concrete'] ?? $id, $parameters, $id);

        if (!$forceNew && ($binding['shared'] ?? false)) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    protected function build(mixed $concrete, array $parameters, string $id): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        if (!is_string($concrete)) {
            return $concrete;
        }

        if (!class_exists($concrete)) {
            if ($concrete !== $id) {
                return $this->resolve($concrete, $parameters, false);
            }

            throw NotFoundException::forId($concrete);
        }

        if (isset($this->building[$concrete])) {
            throw new ContainerException(sprintf(
                'Circular reference detected while building "%s" (%s).',
                $concrete,
                implode(' -> ', [...array_keys($this->building), $concrete]),
            ));
        }

        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('Class "%s" is not instantiable (bind a concrete implementation for "%s").', $concrete, $id));
        }

        $this->building[$concrete] = true;

        try {
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return new $concrete();
            }

            return $reflection->newInstanceArgs($this->resolveArguments($constructor, $parameters));
        } finally {
            unset($this->building[$concrete]);
        }
    }

    protected function resolveAlias(string $id): string
    {
        $seen = [];

        while (isset($this->aliases[$id])) {
            if (isset($seen[$id])) {
                throw new ContainerException(sprintf('Circular alias detected for "%s".', $id));
            }

            $seen[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }

    private function classTypesOf(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if ($type === null) {
            return [];
        }

        $types = $type instanceof ReflectionNamedType ? [$type] : (method_exists($type, 'getTypes') ? $type->getTypes() : []);
        $classes = [];

        foreach ($types as $named) {
            if (!$named instanceof ReflectionNamedType || $named->isBuiltin()) {
                continue;
            }

            $name = $named->getName();

            if ($name === 'self' || $name === 'static') {
                $declaring = $parameter->getDeclaringClass();
                $name = $declaring?->getName() ?? $name;
            }

            $classes[] = $name;
        }

        return $classes;
    }
}