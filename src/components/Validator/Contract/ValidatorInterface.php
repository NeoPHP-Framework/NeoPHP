<?php

declare(strict_types=1);

namespace NeoPHP\Component\Validator\Contract;

use NeoPHP\Component\Validator\Violation\ViolationList;

interface ValidatorInterface
{
    public function validate(mixed $value, ConstraintInterface|array|null $constraints = null, array $groups = [AbstractConstraint::DEFAULT_GROUP]): ViolationList;

    public function validateProperty(object $object, string $property, array $groups = [AbstractConstraint::DEFAULT_GROUP]): ViolationList;

    public function validateOrFail(mixed $value, ConstraintInterface|array|null $constraints = null, array $groups = [AbstractConstraint::DEFAULT_GROUP]): void;
}