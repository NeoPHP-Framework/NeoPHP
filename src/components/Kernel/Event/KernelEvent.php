<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Event;

use NeoPHP\Component\Event\Contract\AbstractEvent;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Kernel\Contract\KernelInterface;

abstract class KernelEvent extends AbstractEvent
{
    public function __construct(protected KernelInterface $kernel, protected Request $request)
    {
    }

    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}