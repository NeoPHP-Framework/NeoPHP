<?php

declare(strict_types=1);

namespace NeoPHP\Component\Service\Helper\Console;

use NeoPHP\Component\Service\Contract\ServiceInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class ServiceListCommand extends AbstractCommand
{
    protected string $name = 'service:list';

    protected string $description = 'Lists the services of config/services.yaml, the aliases and the interfaces bound automatically';

    public function __construct(protected ServiceInterface $services)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $filter = (string) ($input->getArgument(0) ?? '');
        $rows = [];

        foreach ($this->services->getServices() as $id => $definition) {
            $rows[] = [(string) $id, $definition['factory'] !== null ? 'factory' : (string) $definition['class'], $definition['shared'] ? 'yes' : 'no', (string) $definition['source']];
        }

        foreach ($this->services->getAliases() as $alias => $target) {
            $rows[] = [(string) $alias, '@' . $target, '', 'alias'];
        }

        foreach ($this->services->getInterfaces() as $interface => $target) {
            $rows[] = [(string) $interface, is_array($target) ? 'ambiguous: ' . implode(', ', $target) : '@' . $target, '', 'interface'];
        }

        if ($filter !== '') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => stripos($row[0] . ' ' . $row[1], $filter) !== false));
        }

        if ($rows === []) {
            $output->writeln('<comment>No service found.</comment>');

            return self::SUCCESS;
        }

        $output->table(['Id', 'Class', 'Shared', 'Source'], $rows);

        return self::SUCCESS;
    }
}