<?php

declare(strict_types=1);

namespace NeoPHP\Component\Validator\Helper\Controller;

use NeoPHP\Component\Validator\Contract\AbstractConstraint;
use NeoPHP\Component\Validator\Contract\ConstraintInterface;
use NeoPHP\Component\Validator\Contract\ValidatorInterface;
use NeoPHP\Component\Validator\Violation\ViolationList;

trait ValidatorController
{
    abstract protected function get(string $id): mixed;

    protected function validate(mixed $value, ConstraintInterface|array|null $constraints = null, array $groups = [AbstractConstraint::DEFAULT_GROUP]): ViolationList
    {
        return $this->get(ValidatorInterface::class)->validate($value, $constraints, $groups);
    }
}