<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Response;

use NeoPHP\Component\Http\Exception\HttpException;

class RedirectResponse extends Response
{
    public function __construct(protected string $targetUrl, int $status = 302, array $headers = [])
    {
        if ($status < 300 || $status > 399) {
            throw new HttpException(500, 'The status {status} is not a redirection status code.', [], ['status' => $status]);
        }

        if ($targetUrl === '') {
            throw new HttpException(500, 'The redirection URL cannot be empty.');
        }

        parent::__construct('', $status, $headers);

        $this->headers->set('Location', $targetUrl);
        $this->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $this->setContent(sprintf(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta http-equiv="refresh" content="0;url=\'%1$s\'"><title>Redirecting to %1$s</title></head><body>Redirecting to <a href="%1$s">%1$s</a>.</body></html>',
            htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'),
        ));
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }
}