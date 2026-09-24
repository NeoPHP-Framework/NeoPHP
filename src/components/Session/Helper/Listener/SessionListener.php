<?php

declare(strict_types=1);

namespace NeoPHP\Component\Session\Helper\Listener;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Kernel\Event\ResponseEvent;
use NeoPHP\Component\Session\Contract\SessionInterface;

#[AsListener(priority: -200)]
class SessionListener
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if ($this->container->resolved(SessionInterface::class)) {
            $this->container->get(SessionInterface::class)->save();
        }
    }
}