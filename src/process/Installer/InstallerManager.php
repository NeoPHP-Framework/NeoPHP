<?php

declare(strict_types=1);

namespace NeoPHP\Process\Installer;

use NeoPHP\Process\Installer\Contract\AbstractInstaller;

class InstallerManager extends AbstractInstaller
{
    public function __construct(?string $skeletonDir = null)
    {
        parent::__construct($skeletonDir ?? __DIR__ . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'skeleton');
    }
}