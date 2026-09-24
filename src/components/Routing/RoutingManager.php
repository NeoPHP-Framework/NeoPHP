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
    private YamlInterface $yaml;

    public function __construct(?YamlInterface $yaml = null, ?RouteCollection $routes = null)
    {
        parent::__construct($routes);
        $this->yaml = $yaml ?? new YamlManager();
    }

    public function loadYaml(string $file): static
    {
        $this->routes->addCollection((new YamlRouteLoader($this->yaml))->load($file));

        return $this;
    }
}