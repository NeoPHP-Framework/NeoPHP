<?php

declare(strict_types=1);

namespace NeoPHP\Component\View;

use NeoPHP\Component\View\Contract\AbstractView;

class ViewManager extends AbstractView
{
    public function __construct(array $paths = [], string $extension = '.php')
    {
        parent::__construct($extension);

        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }
}