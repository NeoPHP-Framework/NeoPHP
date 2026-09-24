<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Contract;

use NeoPHP\Component\View\Exception\TemplateNotFoundException;
use NeoPHP\Component\View\Exception\ViewException;
use NeoPHP\Component\View\Template\Sections;
use NeoPHP\Component\View\Template\Template;

abstract class AbstractView implements ViewInterface
{
    private const MAX_LAYOUT_DEPTH = 20;

    protected array $paths = [];

    protected array $globals = [];

    protected array $helpers = [];

    public function __construct(protected string $extension = '.php')
    {
    }

    public function render(string $template, array $parameters = []): string
    {
        $sections = new Sections();
        $parameters = array_merge($this->globals, $parameters);
        $current = $template;

        for ($depth = 0; $depth < self::MAX_LAYOUT_DEPTH; $depth++) {
            $context = new Template($this, $sections, $this->helpers, $parameters);
            $output = $context->renderFile($this->resolve($current), $parameters);
            $layout = $context->getLayout();

            if ($layout === null) {
                return $output;
            }

            if (!$sections->has('content') && trim($output) !== '') {
                $sections->set('content', $output);
            }

            $parameters = array_merge($parameters, $context->getLayoutParameters());
            $current = $layout;
        }

        throw new ViewException(sprintf('Too many nested layouts while rendering "%s" (circular extend()?).', $template));
    }

    public function exists(string $template): bool
    {
        try {
            $this->resolve($template);

            return true;
        } catch (TemplateNotFoundException) {
            return false;
        }
    }

    public function addPath(string $path, ?string $namespace = null): static
    {
        $this->paths[$namespace ?? ''][] = rtrim($path, '/\\');

        return $this;
    }

    public function addGlobal(string $name, mixed $value): static
    {
        $this->globals[$name] = $value;

        return $this;
    }

    public function addHelper(string $name, callable $helper): static
    {
        $this->helpers[$name] = $helper;

        return $this;
    }

    protected function resolve(string $template): string
    {
        $namespace = '';
        $name = $template;

        if (str_starts_with($template, '@')) {
            $parts = explode('/', substr($template, 1), 2);
            $namespace = $parts[0];
            $name = $parts[1] ?? '';
        }

        $name = ltrim(str_replace('\\', '/', $name), '/');

        if ($name === '' || str_contains('/' . $name . '/', '/../')) {
            throw new ViewException(sprintf('Invalid template name "%s".', $template));
        }

        if (!str_ends_with($name, $this->extension)) {
            $name .= $this->extension;
        }

        $searched = [];

        foreach ($this->paths[$namespace] ?? [] as $directory) {
            $file = $directory . '/' . $name;
            $searched[] = $file;

            if (is_file($file)) {
                return $file;
            }
        }

        throw TemplateNotFoundException::create($template, $searched);
    }
}