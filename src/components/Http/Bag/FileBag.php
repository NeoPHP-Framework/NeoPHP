<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Bag;

use NeoPHP\Component\Http\Request\UploadedFile;

class FileBag extends ParameterBag
{
    public function __construct(array $files = [])
    {
        parent::__construct($this->normalize($files));
    }

    protected function normalize(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'], $value['error'])) {
                $normalized[$key] = $this->createFile($value);
            } elseif (is_array($value)) {
                $normalized[$key] = $this->normalize($value);
            }
        }

        return $normalized;
    }

    protected function createFile(array $spec): UploadedFile|array|null
    {
        if (is_array($spec['tmp_name'])) {
            $files = [];

            foreach (array_keys($spec['tmp_name']) as $key) {
                $files[$key] = $this->createFile([
                    'tmp_name' => $spec['tmp_name'][$key],
                    'name' => $spec['name'][$key] ?? '',
                    'type' => $spec['type'][$key] ?? null,
                    'size' => $spec['size'][$key] ?? 0,
                    'error' => $spec['error'][$key],
                ]);
            }

            return $files;
        }

        if ((int) $spec['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new UploadedFile(
            (string) $spec['tmp_name'],
            (string) ($spec['name'] ?? ''),
            isset($spec['type']) ? (string) $spec['type'] : null,
            (int) $spec['error'],
            (int) ($spec['size'] ?? 0),
        );
    }
}