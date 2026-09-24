<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Exception;

class TemplateNotFoundException extends ViewException
{
    public static function create(string $template, array $searched): static
    {
        return new static(
            'Template "{template}" not found (looked in: "{searched}").',
            0,
            null,
            ['template' => $template, 'searched' => implode('", "', $searched)],
        );
    }
}