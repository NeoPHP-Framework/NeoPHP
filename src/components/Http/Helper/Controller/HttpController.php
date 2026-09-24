<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Helper\Controller;

use NeoPHP\Component\Http\Contract\HttpInterface;
use NeoPHP\Component\Http\Exception\AccessDeniedHttpException;
use NeoPHP\Component\Http\Exception\NotFoundHttpException;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;

trait HttpController
{
    abstract protected function get(string $id): mixed;

    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return $this->get(HttpInterface::class)->json($data, $status, $headers);
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return $this->get(HttpInterface::class)->redirect($url, $status);
    }

    protected function createNotFoundException(string $message = 'Not Found', array $context = []): NotFoundHttpException
    {
        return new NotFoundHttpException($message, $context);
    }

    protected function createAccessDeniedException(string $message = 'Forbidden', array $context = []): AccessDeniedHttpException
    {
        return new AccessDeniedHttpException($message, $context);
    }
}