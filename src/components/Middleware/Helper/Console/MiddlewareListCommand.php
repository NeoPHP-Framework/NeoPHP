<?php

declare(strict_types=1);

namespace NeoPHP\Component\Middleware\Helper\Console;

use NeoPHP\Component\Middleware\Contract\MiddlewareManagerInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class MiddlewareListCommand extends AbstractCommand
{
    protected string $name = 'middleware:list';

    protected string $description = 'Lists the global middlewares, the aliases and the groups';

    public function __construct(protected MiddlewareManagerInterface $middlewares)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $rows = [];

        foreach ($this->middlewares->getGlobal() as $index => $class) {
            $rows[] = ['global', (string) ($index + 1), $class];
        }

        foreach ($this->middlewares->getAliases() as $name => $class) {
            $rows[] = ['alias', (string) $name, $class];
        }

        foreach ($this->middlewares->getGroups() as $name => $items) {
            $rows[] = ['group', (string) $name, implode(', ', $items)];
        }

        if ($rows === []) {
            $output->writeln('<comment>No middleware defined.</comment>');

            return self::SUCCESS;
        }

        $output->table(['Type', 'Name', 'Middleware'], $rows);

        return self::SUCCESS;
    }
}