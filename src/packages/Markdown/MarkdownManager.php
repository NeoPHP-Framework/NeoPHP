<?php

declare(strict_types=1);

namespace NeoPHP\Package\Markdown;

use Closure;
use NeoPHP\Package\Markdown\Contract\AbstractMarkdownParser;

class MarkdownManager extends AbstractMarkdownParser
{
    public function __construct(string $rootPath, ?string $templatesPath = null, ?Closure $viewResolver = null)
    {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->templatesPath = rtrim(str_replace('\\', '/', $templatesPath ?? $this->rootPath . '/templates'), '/');
        $this->viewResolver = $viewResolver;
    }
}