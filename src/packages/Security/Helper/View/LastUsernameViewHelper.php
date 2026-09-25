<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\View;

use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Security\Contract\SecurityInterface;

class LastUsernameViewHelper implements ViewFunctionInterface
{
    public function __construct(protected SecurityInterface $security)
    {
    }

    public function getName(): string
    {
        return 'last_username';
    }

    public function __invoke(): string
    {
        return $this->security->getLastUsername();
    }
}