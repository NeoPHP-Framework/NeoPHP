<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Helper\Controller;

use NeoPHP\Component\Http\Contract\HttpInterface;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Routing\Contract\RoutingInterface;

trait RoutingController
{
    abstract protected function get(string $id): mixed;

    protected function generateUrl(string $route, array $parameters = []): string
    {
        return $this->get(RoutingInterface::class)->generate($route, $parameters);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->get(HttpInterface::class)->redirect($this->generateUrl($route, $parameters), $status);
    }
}