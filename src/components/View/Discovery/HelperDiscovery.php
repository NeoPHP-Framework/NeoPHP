<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Discovery;

use FilesystemIterator;
use NeoPHP\Component\View\Contract\ViewHelperInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

class HelperDiscovery
{
    public const DIRECTORY = 'Helper/View';

    protected array $sources = [];

    public function __construct(array $sources = [])
    {
        foreach ($sources as $path => $namespace) {
            $this->addSource((string) $path, (string) $namespace);
        }
    }

    public function addSource(string $path, string $namespace): static
    {
        $this->sources[rtrim(str_replace('\\', '/', $path), '/')] = rtrim($namespace, '\\') . '\\';

        return $this;
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    public function discover(): array
    {
        $classes = [];

        foreach ($this->sources as $path => $namespace) {
            $classes = [...$classes, ...$this->scan($path, $namespace)];
        }

        return array_values(array_unique($classes));
    }

    protected function scan(string $path, string $namespace): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $classes = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = ltrim(substr(str_replace('\\', '/', $file->getPathname()), strlen($path)), '/');

            if (!str_contains('/' . dirname($relative) . '/', '/' . static::DIRECTORY . '/')) {
                continue;
            }

            $class = $namespace . str_replace('/', '\\', substr($relative, 0, -4));

            if (!class_exists($class) || !is_subclass_of($class, ViewHelperInterface::class)) {
                continue;
            }

            if ((new ReflectionClass($class))->isInstantiable()) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }
}