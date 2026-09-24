<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Cache;

use NeoPHP\Component\Routing\Exception\RoutingException;
use NeoPHP\Component\Routing\Route\Route;
use NeoPHP\Component\Routing\Route\RouteCollection;

class RouteCache
{
    public function __construct(protected string $file, protected bool $debug = false)
    {
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function load(callable $builder): RouteCollection
    {
        $cached = $this->read();

        if ($cached !== null && (!$this->debug || $this->isFresh((array) ($cached['resources'] ?? [])))) {
            return $this->hydrate((array) ($cached['routes'] ?? []));
        }

        [$routes, $resources] = $builder();
        $this->write($routes, $resources);

        return $routes;
    }

    public function clear(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
    }

    protected function read(): ?array
    {
        if (!is_file($this->file)) {
            return null;
        }

        $data = @include $this->file;

        return is_array($data) ? $data : null;
    }

    protected function isFresh(array $resources): bool
    {
        foreach ($resources as $path => $time) {
            if (!file_exists((string) $path) || (int) filemtime((string) $path) !== (int) $time) {
                return false;
            }
        }

        return true;
    }

    protected function hydrate(array $routes): RouteCollection
    {
        $collection = new RouteCollection();

        foreach ($routes as $data) {
            $route = new Route($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6]);
            $collection->add($route->setSource((string) $data[7]));
        }

        return $collection;
    }

    protected function write(RouteCollection $routes, array $resources): void
    {
        $data = ['resources' => $resources, 'routes' => []];

        foreach ($routes as $route) {
            $data['routes'][] = [
                $route->getName(),
                $route->getPath(),
                $route->getController(),
                $route->getMethods(),
                $route->getRequirements(),
                $route->getDefaults(),
                $route->getOptions(),
                $route->getSource(),
            ];
        }

        $directory = dirname($this->file);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RoutingException(sprintf('Unable to create the cache directory "%s".', $directory));
        }

        $temporary = $this->file . '.' . uniqid('', true) . '.tmp';

        if (file_put_contents($temporary, '<?php return ' . var_export($data, true) . ";\n") === false || !rename($temporary, $this->file)) {
            @unlink($temporary);

            throw new RoutingException(sprintf('Unable to write the routes cache "%s".', $this->file));
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($this->file, true);
        }
    }
}