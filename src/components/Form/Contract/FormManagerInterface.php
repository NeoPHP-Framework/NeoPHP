<?php

declare(strict_types=1);

namespace NeoPHP\Component\Form\Contract;

use NeoPHP\Component\Csrf\Contract\CsrfInterface;
use NeoPHP\Component\Form\FormBuilder;
use NeoPHP\Component\Form\Renderer\FormRenderer;
use NeoPHP\Component\Form\ResolvedType;
use NeoPHP\Component\Form\Type\FormType;
use NeoPHP\Component\Validator\Contract\ValidatorInterface;

interface FormManagerInterface
{
    public function create(string $type = FormType::class, mixed $data = null, array $options = []): FormInterface;

    public function createNamed(string $name, string $type = FormType::class, mixed $data = null, array $options = []): FormInterface;

    public function createBuilder(string $type = FormType::class, mixed $data = null, array $options = []): FormBuilder;

    public function createNamedBuilder(string $name, string $type = FormType::class, mixed $data = null, array $options = []): FormBuilder;

    public function getType(string $type): ResolvedType;

    public function getValidator(): ?ValidatorInterface;

    public function getCsrf(): ?CsrfInterface;

    public function getRenderer(): FormRenderer;

    public function getConfig(): array;
}