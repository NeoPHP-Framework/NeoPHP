<?php

declare(strict_types=1);

namespace NeoPHP\Component\Event\Contract;

interface EventDispatcherInterface
{
    public function dispatch(object $event): object;

    public function addListener(string $event, callable|array $listener, int $priority = 0): static;

    public function addSubscriber(string|EventSubscriberInterface $subscriber): static;

    public function getListeners(?string $event = null): array;

    public function hasListeners(string $event): bool;
}