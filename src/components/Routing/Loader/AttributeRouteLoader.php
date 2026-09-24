<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Loader;

use FilesystemIterator;
use NeoPHP\Component\Routing\Attribute\Route as RouteAttribute;
use NeoPHP\Component\Routing\Exception\RoutingException;
use NeoPHP\Component\Routing\Route\Route;
use NeoPHP\Component\Routing\Route\RouteCollection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

class AttributeRouteLoader
{
    protected array $resources = [];

    public function load(string $path): RouteCollection
    {
        $real = realpath($path);

        if ($real === false) {
            throw new RoutingException(sprintf('The controllers resource "%s" does not exist.', $path));
        }

        $collection = new RouteCollection();

        foreach ($this->files($real) as $file) {
            foreach ($this->classesIn($file) as $class) {
                foreach ($this->loadClass($class) as $route) {
                    $this->append($collection, $route);
                }
            }
        }

        return $collection;
    }

    public function loadClass(string $class): RouteCollection
    {
        $collection = new RouteCollection();

        if (!class_exists($class)) {
            return $collection;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()) {
            return $collection;
        }

        $globals = $this->attributes($reflection->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF));
        $hasMethodRoutes = false;

        foreach ($reflection->getMethods() as $method) {
            $attributes = $this->attributes($method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF));

            if ($attributes === []) {
                continue;
            }

            if (!$method->isPublic() || $method->isStatic() || $method->isConstructor()) {
                throw new RoutingException(sprintf('The route attribute of "%s::%s()" must be on a public, non-static method.', $class, $method->getName()));
            }

            $hasMethodRoutes = true;

            foreach ($globals === [] ? [null] : $globals as $global) {
                foreach ($attributes as $attribute) {
                    $this->append($collection, $this->createRoute($reflection, $method, $attribute, $global));
                }
            }
        }

        if (!$hasMethodRoutes && $globals !== [] && $reflection->hasMethod('__invoke')) {
            $invoke = $reflection->getMethod('__invoke');

            foreach ($globals as $attribute) {
                $this->append($collection, $this->createRoute($reflection, $invoke, $attribute, null));
            }
        }

        return $collection;
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    protected function createRoute(ReflectionClass $class, ReflectionMethod $method, RouteAttribute $attribute, ?RouteAttribute $global): Route
    {
        $name = $attribute->name ?? $this->defaultName($class->getName(), $method->getName(), $global?->name !== null);
        $name = rtrim(($global?->name ?? '') . $name, '_');
        $controller = $method->getName() === '__invoke' ? $class->getName() : $class->getName() . '::' . $method->getName();

        $route = new Route(
            $name,
            ($global?->path ?? '') . '/' . ltrim($attribute->path, '/'),
            $controller,
            $attribute->getMethods() !== [] ? $attribute->getMethods() : ($global?->getMethods() ?? []),
            array_replace($global?->requirements ?? [], $attribute->requirements),
            array_replace($global?->defaults ?? [], $attribute->defaults),
            array_replace($global?->options ?? [], $attribute->options),
        );

        return $route->setSource($class->getName() . '::' . $method->getName() . '()');
    }

    protected function defaultName(string $class, string $method, bool $prefixed = false): string
    {
        $parts = [];

        foreach ($prefixed ? [] : explode('\\', $class) as $segment) {
            $segment = (string) preg_replace('/Controller$/', '', $segment);

            if ($segment !== '') {
                $parts[] = $segment;
            }
        }

        if ($method !== '__invoke') {
            $parts[] = $method;
        }

        return strtolower((string) preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', implode('_', $parts)));
    }

    protected function attributes(array $attributes): array
    {
        return array_map(static fn (ReflectionAttribute $attribute): RouteAttribute => $attribute->newInstance(), $attributes);
    }

    protected function append(RouteCollection $collection, Route $route): void
    {
        $existing = $collection->get($route->getName());

        if ($existing !== null) {
            throw new RoutingException(sprintf('The route "%s" is defined twice: in "%s" and in "%s".', $route->getName(), $existing->getSource(), $route->getSource()));
        }

        $collection->add($route);
    }

    protected function files(string $path): array
    {
        if (is_file($path)) {
            $this->resources[$path] = (int) filemtime($path);

            return [$path];
        }

        $this->resources[$path] = (int) filemtime($path);
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            if ($file->isDir()) {
                $this->resources[$file->getPathname()] = (int) $file->getMTime();
            } elseif ($file->getExtension() === 'php') {
                $this->resources[$file->getPathname()] = (int) $file->getMTime();
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    protected function classesIn(string $file): array
    {
        $content = (string) file_get_contents($file);

        if (!str_contains($content, '#[')) {
            return [];
        }

        $tokens = token_get_all($content);
        $count = count($tokens);
        $namespace = '';
        $classes = [];

        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';

                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }

                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED], true)) {
                        $namespace .= $tokens[$j][1];
                    }
                }

                continue;
            }

            if ($tokens[$i][0] !== T_CLASS || $this->previousToken($tokens, $i) === T_DOUBLE_COLON || $this->previousToken($tokens, $i) === T_NEW) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $classes[] = ltrim($namespace . '\\' . $tokens[$j][1], '\\');
                    break;
                }

                if ($tokens[$j] === '{' || $tokens[$j] === '(') {
                    break;
                }
            }
        }

        return $classes;
    }

    protected function previousToken(array $tokens, int $index): int|string|null
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
        }

        return null;
    }
}