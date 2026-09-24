<?php

declare(strict_types=1);

$publicDir = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\');
$path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

if ($path !== '/' && is_file($publicDir . $path) && !str_ends_with($path, '.php')) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $publicDir . DIRECTORY_SEPARATOR . 'index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $publicDir . DIRECTORY_SEPARATOR . 'index.php';