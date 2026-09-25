<?php

declare(strict_types=1);

namespace NeoPHP\Component\Form;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Csrf\Contract\CsrfInterface;
use NeoPHP\Component\Form\Contract\AbstractFormManager;
use NeoPHP\Component\Validator\Contract\ValidatorInterface;

class FormManager extends AbstractFormManager
{
    public function __construct(?ContainerInterface $container = null, ?ValidatorInterface $validator = null, ?CsrfInterface $csrf = null, array $config = [])
    {
        $this->container = $container;
        $this->validator = $validator;
        $this->csrf = $csrf;
        $this->config = array_replace(static::DEFAULT_CONFIG, $config);
    }
}