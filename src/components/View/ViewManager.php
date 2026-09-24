<?php

declare(strict_types=1);

namespace NeoPHP\Component\View;

use NeoPHP\Component\View\Contract\AbstractView;
use NeoPHP\Component\View\Engine\EngineInterface;
use NeoPHP\Component\View\Engine\PhpEngine;

class ViewManager extends AbstractView
{
    public function __construct(array $paths = [], ?array $engines = null)
    {
        foreach ($engines ?? [new PhpEngine()] as $engine) {
            if ($engine instanceof EngineInterface) {
                $this->addEngine($engine);
            }
        }

        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }
}