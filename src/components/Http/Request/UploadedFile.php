<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Request;

use NeoPHP\Component\Http\Exception\HttpException;

class UploadedFile
{
    public const ERRORS = [
        UPLOAD_ERR_OK => 'The file was uploaded successfully.',
        UPLOAD_ERR_INI_SIZE => 'The file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE => 'The file exceeds the MAX_FILE_SIZE directive of the form.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write the file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
    ];

    protected bool $moved = false;

    public function __construct(
        protected string $path,
        protected string $originalName,
        protected ?string $mimeType = null,
        protected int $error = UPLOAD_ERR_OK,
        protected int $size = 0,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    public function getClientOriginalExtension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    public function getClientMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getErrorMessage(): string
    {
        return self::ERRORS[$this->error] ?? 'Unknown upload error.';
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && !$this->moved && (PHP_SAPI === 'cli' || is_uploaded_file($this->path));
    }

    public function move(string $directory, ?string $name = null): string
    {
        if (!$this->isValid()) {
            throw new HttpException(400, 'The file "{name}" cannot be moved: {error}', [], ['name' => $this->originalName, 'error' => $this->getErrorMessage()]);
        }

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new HttpException(500, 'Unable to create the directory "{directory}".', [], ['directory' => $directory]);
        }

        $target = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . ($name ?? basename($this->originalName));
        $moved = PHP_SAPI === 'cli' ? rename($this->path, $target) : move_uploaded_file($this->path, $target);

        if (!$moved) {
            throw new HttpException(500, 'Unable to move the file "{name}" to "{target}".', [], ['name' => $this->originalName, 'target' => $target]);
        }

        $this->moved = true;

        return $target;
    }
}