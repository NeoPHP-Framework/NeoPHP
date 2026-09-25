<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\View;

use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Security\Contract\SecurityInterface;

class LogoutPathViewHelper implements ViewFunctionInterface
{
    public function __construct(protected SecurityInterface $security)
    {
    }

    public function getName(): string
    {
        return 'logout_path';
    }

    public function __invoke(?string $firewall = null): ?string
    {
        return $this->security->getLogoutPath($firewall);
    }
}