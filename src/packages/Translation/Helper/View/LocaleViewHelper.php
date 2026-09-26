<?php

declare(strict_types=1);

namespace NeoPHP\Package\Translation\Helper\View;

use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;

class LocaleViewHelper implements ViewFunctionInterface
{
    public function __construct(protected TranslatorInterface $translator)
    {
    }

    public function getName(): string
    {
        return 'locale';
    }

    public function __invoke(): string
    {
        return $this->translator->getLocale();
    }
}