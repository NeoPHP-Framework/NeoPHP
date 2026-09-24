<?php

declare(strict_types=1);

namespace NeoPHP\Component\Event;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Contract\AbstractEventDispatcher;

class EventManager extends AbstractEventDispatcher
{
    public function __construct(?ContainerInterface $container = null, array $listeners = [])
    {
        $this->container = $container;

        foreach ($listeners as $event => $entries) {
            foreach ((array) $entries as $entry) {
                $this->addListener((string) $event, [(string) $entry[0], (string) $entry[1]], (int) ($entry[2] ?? 0));
            }
        }
    }
}