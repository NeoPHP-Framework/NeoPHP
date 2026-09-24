<?php

declare(strict_types=1);

namespace NeoPHP\Component\Cookie\Helper\Listener;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Cookie\Contract\CookieInterface;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Kernel\Event\ResponseEvent;

#[AsListener(priority: -100)]
class CookieListener
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if ($this->container->resolved(CookieInterface::class)) {
            $this->container->get(CookieInterface::class)->apply($event->getResponse());
        }
    }
}