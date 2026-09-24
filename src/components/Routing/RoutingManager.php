<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing;

use NeoPHP\Component\Routing\Contract\AbstractRouting;
use NeoPHP\Component\Routing\Loader\YamlRouteLoader;
use NeoPHP\Component\Routing\Route\RouteCollection;
use NeoPHP\Package\Yaml\Contract\YamlInterface;
use NeoPHP\Package\Yaml\YamlManager;

class RoutingManager extends AbstractRouting
{
    protected YamlInterface $yaml;

    protected mixed $resolver;

    public function __construct(?YamlInterface $yaml = null, ?RouteCollection $routes = null, ?callable $resolver = null)
    {
        parent::__construct($routes);
        $this->yaml = $yaml ?? new YamlManager();
        $this->resolver = $resolver;
    }

    public function loadYaml(string $file): static
    {
        $this->routes->addCollection((new YamlRouteLoader($this->yaml, $this->resolver))->load($file));

        return $this;
    }
}