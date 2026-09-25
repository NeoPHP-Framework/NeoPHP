<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\Listener;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Kernel\Event\ExceptionEvent;
use NeoPHP\Component\Kernel\Event\RequestEvent;
use NeoPHP\Package\Security\Contract\SecurityInterface;
use NeoPHP\Package\Security\Provider\SecurityProvider;

class SecurityListener
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    #[AsListener(priority: 8)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$this->container->has(SecurityInterface::class) || !$this->container->get(SecurityProvider::CONFIG_ID)['enabled']) {
            return;
        }

        $response = $this->container->get(SecurityInterface::class)->handleRequest($event->getRequest());

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    #[AsListener(priority: 8)]
    public function onException(ExceptionEvent $event): void
    {
        if (!$this->container->resolved(SecurityInterface::class)) {
            return;
        }

        $response = $this->container->get(SecurityInterface::class)->handleException($event->getRequest(), $event->getThrowable());

        if ($response !== null) {
            $event->setResponse($response);
        }
    }
}