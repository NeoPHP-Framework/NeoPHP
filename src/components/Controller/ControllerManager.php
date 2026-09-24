<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller;

use Closure;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Controller\Contract\ControllerInterface;
use NeoPHP\Component\Controller\Contract\ControllerResolverInterface;
use NeoPHP\Component\Controller\Exception\ControllerException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Stringable;

class ControllerManager implements ControllerResolverInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function resolve(mixed $controller): callable
    {
        if (is_string($controller) && str_contains($controller, '::')) {
            $controller = explode('::', $controller, 2);
        }

        if (is_array($controller) && count($controller) === 2 && is_string($controller[0] ?? null) && is_string($controller[1] ?? null)) {
            [$class, $method] = $controller;
            $callable = [$this->instantiate($class), $method];

            if (!is_callable($callable)) {
                throw new ControllerException('The controller method "{class}::{method}()" does not exist or is not public.', 0, null, ['class' => $class, 'method' => $method]);
            }

            return $callable;
        }

        if (is_string($controller) && class_exists($controller)) {
            $instance = $this->instantiate($controller);

            if (!is_callable($instance)) {
                throw new ControllerException('The controller "{class}" is not invokable: add an __invoke() method or use "{class}::method".', 0, null, ['class' => $controller]);
            }

            return $instance;
        }

        if (is_callable($controller)) {
            return $controller;
        }

        throw new ControllerException('Unable to resolve the controller "{controller}". Expected "Class::method", an invokable class or a callable.', 0, null, [
            'controller' => is_string($controller) ? $controller : get_debug_type($controller),
        ]);
    }

    public function resolveArguments(callable $controller, array $routeParameters = []): array
    {
        $reflection = $this->reflect($controller);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->resolveArgument($parameter, $routeParameters, $reflection);
        }

        return $arguments;
    }

    public function dispatch(mixed $controller, array $routeParameters = []): string
    {
        $callable = $this->resolve($controller);
        $result = $callable(...$this->resolveArguments($callable, $routeParameters));

        if ($result === null) {
            return '';
        }

        if (is_string($result) || $result instanceof Stringable) {
            return (string) $result;
        }

        throw new ControllerException('A controller must return a string or null ({type} returned).', 0, null, ['type' => get_debug_type($result)]);
    }

    protected function resolveArgument(ReflectionParameter $parameter, array $routeParameters, ReflectionFunctionAbstract $function): mixed
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if (array_key_exists($name, $routeParameters)) {
            return $this->cast($routeParameters[$name], $parameter);
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $this->container->has($type->getName())) {
            return $this->container->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ControllerException('Unable to resolve the argument "${argument}" of "{controller}": it is neither a route parameter nor a service.', 0, null, [
            'argument' => $name,
            'controller' => $this->describe($function),
        ]);
    }

    protected function cast(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin() || !is_scalar($value)) {
            return $value;
        }

        $cast = match ($type->getName()) {
            'int' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string' => (string) $value,
            default => $value,
        };

        if ($cast === null) {
            throw (new ControllerException('The route parameter "{parameter}" must be of type {type}.', 0, null, [
                'parameter' => $parameter->getName(),
                'type' => $type->getName(),
            ]))->setStatusCode(404);
        }

        return $cast;
    }

    protected function instantiate(string $class): object
    {
        if (!class_exists($class)) {
            throw new ControllerException('The controller class "{class}" does not exist (check its namespace and the composer autoload).', 0, null, ['class' => $class]);
        }

        $instance = $this->container->get($class);

        if ($instance instanceof ControllerInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
    }

    protected function reflect(callable $controller): ReflectionFunctionAbstract
    {
        if (is_array($controller)) {
            return new ReflectionMethod($controller[0], $controller[1]);
        }

        if (is_object($controller) && !$controller instanceof Closure) {
            return new ReflectionMethod($controller, '__invoke');
        }

        return new ReflectionFunction(Closure::fromCallable($controller));
    }

    protected function describe(ReflectionFunctionAbstract $function): string
    {
        return $function instanceof ReflectionMethod
            ? $function->getDeclaringClass()->getName() . '::' . $function->getName() . '()'
            : $function->getName() . '()';
    }
}