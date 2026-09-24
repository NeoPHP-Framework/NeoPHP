<?php

declare(strict_types=1);

namespace NeoPHP\Component\Session\Helper\View;

use NeoPHP\Component\Session\Contract\SessionInterface;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class SessionViewHelper implements ViewFunctionInterface
{
    public function __construct(protected SessionInterface $session)
    {
    }

    public function getName(): string
    {
        return 'session';
    }

    public function __invoke(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }
}