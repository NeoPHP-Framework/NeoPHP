<?php

declare(strict_types=1);

namespace NeoPHP\Component\Event\Helper\Console;

use NeoPHP\Component\Event\Contract\AbstractEventDispatcher;
use NeoPHP\Component\Event\Contract\EventDispatcherInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class EventListCommand extends AbstractCommand
{
    protected string $name = 'event:list';

    protected string $description = 'Lists the events and their listeners in the order they are called';

    public function __construct(protected EventDispatcherInterface $events)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $filter = (string) ($input->getArgument(0) ?? '');
        $rows = [];

        foreach ($this->events->getListeners() as $event => $listeners) {
            if ($filter !== '' && stripos((string) $event, $filter) === false) {
                continue;
            }

            foreach ($listeners as $index => [$listener, $priority]) {
                $rows[] = [$index === 0 ? (string) $event : '', (string) ($index + 1), AbstractEventDispatcher::describe($listener), (string) $priority];
            }
        }

        if ($rows === []) {
            $output->writeln('<comment>No listener found.</comment>');

            return self::SUCCESS;
        }

        $output->table(['Event', '#', 'Listener', 'Priority'], $rows);

        return self::SUCCESS;
    }
}