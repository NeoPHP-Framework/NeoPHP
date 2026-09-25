<?php

declare(strict_types=1);

namespace NeoPHP\Package\Debug\Helper\Listener;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Kernel\Event\ResponseEvent;
use NeoPHP\Package\Debug\Contract\DebugInterface;

#[AsListener(priority: -150)]
class DebugListener
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->container->resolved(DebugInterface::class)) {
            return;
        }

        $debug = $this->container->get(DebugInterface::class);

        if ($debug->hasPending()) {
            $debug->injectInto($event->getResponse());
        }
    }
}