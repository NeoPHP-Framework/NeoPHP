<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Command;

use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class RouteListCommand extends AbstractCommand
{
    protected string $name = 'route:list';

    protected string $description = 'Lists the application routes';

    public function __construct(protected RoutingInterface $routing)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $rows = [];

        foreach ($this->routing->getRoutes() as $route) {
            $controller = $route->getController();

            $rows[] = [
                $route->getName(),
                $route->getMethods() === [] ? 'ANY' : implode('|', $route->getMethods()),
                $route->getPath(),
                is_array($controller) ? implode('::', array_map('strval', $controller)) : (is_string($controller) ? $controller : get_debug_type($controller)),
            ];
        }

        if ($rows === []) {
            $output->writeln('<comment>No route defined in config/routes.yaml.</comment>');

            return self::SUCCESS;
        }

        $output->table(['Name', 'Method', 'Path', 'Controller'], $rows);

        return self::SUCCESS;
    }
}