<?php

declare(strict_types=1);

namespace NeoPHP\Component\Flash\Helper\Controller;

use NeoPHP\Component\Flash\Contract\FlashInterface;

trait FlashController
{
    abstract protected function get(string $id): mixed;

    protected function addFlash(string $type, string $message): void
    {
        $this->get(FlashInterface::class)->add($type, $message);
    }
}