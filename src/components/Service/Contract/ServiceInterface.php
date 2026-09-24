<?php

declare(strict_types=1);

namespace NeoPHP\Component\Service\Contract;

interface ServiceInterface
{
    public function register(array $definitions): static;

    public function getServices(): array;

    public function getAliases(): array;

    public function getInterfaces(): array;
}