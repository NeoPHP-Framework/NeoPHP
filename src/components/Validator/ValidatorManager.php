<?php

declare(strict_types=1);

namespace NeoPHP\Component\Validator;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Validator\Contract\AbstractValidator;
use NeoPHP\Component\Validator\Metadata\MetadataFactory;

class ValidatorManager extends AbstractValidator
{
    public function __construct(?ContainerInterface $container = null, ?MetadataFactory $metadata = null)
    {
        $this->container = $container;
        $this->metadata = $metadata ?? new MetadataFactory();
    }
}