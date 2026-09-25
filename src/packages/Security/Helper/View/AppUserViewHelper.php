<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\View;

use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Security\Contract\SecurityInterface;
use NeoPHP\Package\Security\Contract\UserInterface;

class AppUserViewHelper implements ViewFunctionInterface
{
    public function __construct(protected SecurityInterface $security)
    {
    }

    public function getName(): string
    {
        return 'app_user';
    }

    public function __invoke(): ?UserInterface
    {
        return $this->security->getUser();
    }
}