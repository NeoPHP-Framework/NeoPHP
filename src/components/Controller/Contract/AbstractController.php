<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Contract;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Controller\Exception\ControllerException;
use NeoPHP\Component\Exception\FrameworkException;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\View\Contract\ViewInterface;

abstract class AbstractController implements ControllerInterface
{
    protected ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function render(string $template, array $parameters = []): string
    {
        return $this->get(ViewInterface::class)->render($template, $parameters);
    }

    protected function generateUrl(string $route, array $parameters = []): string
    {
        return $this->get(RoutingInterface::class)->generate($route, $parameters);
    }

    protected function createNotFoundException(string $message = 'Not Found', array $context = []): FrameworkException
    {
        return (new ControllerException($message, 0, null, $context))->setStatusCode(404);
    }

    protected function get(string $id): mixed
    {
        if ($this->container === null) {
            throw new ControllerException('The container is not set on "{controller}".', 0, null, ['controller' => static::class]);
        }

        return $this->container->get($id);
    }

    protected function has(string $id): bool
    {
        return $this->container !== null && $this->container->has($id);
    }
}