<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Helper\Controller;

use NeoPHP\Component\Http\Contract\HttpInterface;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\View\Contract\ViewInterface;

trait ViewController
{
    abstract protected function get(string $id): mixed;

    protected function render(string $template, array $parameters = [], int $status = 200, array $headers = []): Response
    {
        return $this->get(HttpInterface::class)->createResponse($this->renderView($template, $parameters), $status, $headers);
    }

    protected function renderView(string $template, array $parameters = []): string
    {
        return $this->get(ViewInterface::class)->render($template, $parameters);
    }
}