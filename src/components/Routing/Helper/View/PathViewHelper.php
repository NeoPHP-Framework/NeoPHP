<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Helper\View;

use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class PathViewHelper implements ViewFunctionInterface
{
    public function __construct(protected RoutingInterface $routing)
    {
    }

    public function getName(): string
    {
        return 'path';
    }

    public function __invoke(string $name, array $parameters = []): string
    {
        return $this->routing->generate($name, $parameters);
    }
}