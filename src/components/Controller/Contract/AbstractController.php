<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Contract;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Controller\Exception\ControllerException;
use NeoPHP\Component\Http\Contract\HttpInterface;
use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\View\Contract\ViewInterface;

abstract class AbstractController implements ControllerInterface
{
    protected ?ContainerInterface $container = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function render(string $template, array $parameters = [], int $status = 200, array $headers = []): Response
    {
        return $this->http()->createResponse($this->renderView($template, $parameters), $status, $headers);
    }

    protected function renderView(string $template, array $parameters = []): string
    {
        return $this->get(ViewInterface::class)->render($template, $parameters);
    }

    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return $this->http()->json($data, $status, $headers);
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return $this->http()->redirect($url, $status);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    protected function generateUrl(string $route, array $parameters = []): string
    {
        return $this->get(RoutingInterface::class)->generate($route, $parameters);
    }

    protected function createNotFoundException(string $message = 'Not Found', array $context = []): NotFoundHttpException
    {
        return new NotFoundHttpException($message, $context);
    }

    protected function createAccessDeniedException(string $message = 'Forbidden', array $context = []): AccessDeniedHttpException
    {
        return new AccessDeniedHttpException($message, $context);
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

    private function http(): HttpInterface
    {
        return $this->get(HttpInterface::class);
    }
}