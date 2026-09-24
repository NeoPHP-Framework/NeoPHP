# NeoPHP v1.x

## Overview

NeoPHP v1.x is the base of the framework. It has no dependency other than PHP 8.2. It lets an application:

- define its routes in `config/routes.yaml`
- read YAML files
- render PHP views stored in `templates/`

## Installation (development)

Until the framework is published on Packagist, require it through a Composer path repository (symlink):

```json
{
    "repositories": [
        { "type": "path", "url": "../neophp", "options": { "symlink": true } }
    ],
    "require": {
        "neophp/framework": "*@dev"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "Neo\\": "neo/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

## Application files

`neo/Kernel.php`

```php
<?php

declare(strict_types=1);

namespace Neo;

use NeoPHP\Component\Kernel\KernelManager;

class Kernel extends KernelManager
{
}
```

`public/index.php`

```php
<?php

declare(strict_types=1);

use Neo\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Kernel())->run();
```

## Routes

`config/routes.yaml`

```yaml
home:
  path: /
  controller: App\Controller\HomeController::index
  methods: [GET]

user_show:
  path: /user/{id}
  controller: App\Controller\UserController::show
  requirements: { id: '\d+' }

page:
  path: /page/{slug}
  controller: App\Controller\PageController::show
  defaults: { slug: home }

admin:
  resource: routes/admin.yaml
  prefix: /admin
  name_prefix: admin_
```

| Key | Description |
|---|---|
| `path` | URL pattern, placeholders written `{name}` |
| `controller` | `Class::method`, or an invokable class |
| `methods` | allowed HTTP methods (all when omitted) |
| `requirements` | regex per placeholder (default `[^/]+`) |
| `defaults` | default values; a trailing placeholder with a default is optional |
| `resource` | imports another routes file (relative to the current file) |
| `prefix` / `name_prefix` | prefix applied to the imported paths / names |

A path that matches no route returns a 404. A path that matches with the wrong HTTP method returns a 405.

## Controllers

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use NeoPHP\Component\Controller\Contract\AbstractController;

class UserController extends AbstractController
{
    public function show(int $id): string
    {
        if ($id > 100) {
            throw $this->createNotFoundException('User {id} not found.', ['id' => $id]);
        }

        return $this->render('user/show', ['id' => $id]);
    }
}
```

Controller arguments are resolved from the route parameters (cast to `int`, `float`, `bool` or `string`), then from the container (class type-hints), then from default values. A controller returns a string.

`AbstractController` shortcuts: `render()`, `generateUrl()`, `createNotFoundException()`, `get()`, `has()`.

## Views

Templates are PHP files in `templates/`: `render('user/show')` renders `templates/user/show.php`. Inside a template, `$this` gives access to:

| Method | Description |
|---|---|
| `$this->extend('base')` | renders the template inside the `base` layout |
| `$this->start('name')` / `$this->stop()` | captures a section |
| `$this->section('name')` | outputs a section in a layout |
| `$this->include('partials/menu', [...])` | renders a partial |
| `$this->e($value)` | escapes a value for HTML |
| `$this->path('route', [...])` | generates the URL of a route |
| `$this->asset('app.css')` | returns `/app.css` |

`templates/base.php`

```php
<!DOCTYPE html>
<html>
<head><title><?= $this->e($title ?? 'NeoPHP') ?></title></head>
<body>
<?= $this->section('content') ?>
</body>
</html>
```

`templates/user/show.php`

```php
<?php $this->extend('base', ['title' => 'User']) ?>

<?php $this->start('content') ?>
<h1>User #<?= $this->e($id) ?></h1>
<a href="<?= $this->path('home') ?>">Home</a>
<?php $this->stop() ?>
```

## YAML

```php
use NeoPHP\Package\Yaml\YamlManager;

$data = (new YamlManager())->parseFile('config/app.yaml');
```

Supported: mappings, sequences, flow collections (`[a, b]`, `{a: 1}`), quoted strings, booleans, null, numbers, block scalars (`|`, `>`) and comments. Anchors, aliases and tags are not supported.

## Exceptions

Every framework exception extends `NeoPHP\Component\Exception\FrameworkException`:

```php
$exception = new FrameworkException('User {id} not found.', 0, null, ['id' => 42]);

$exception->getMessage();
$exception->getCode();
$exception->getLine();
$exception->getFile();
$exception->getContext();
$exception->getStatusCode();
$exception->getStackTrace();
$exception->getPreviousExceptions();
$exception->toArray();
```

`{placeholders}` in the message are replaced by the context values. The status code (500 by default) is used for the HTTP response: routing exceptions use 404 and 405.

## Environment

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `dev` | environment name |
| `APP_DEBUG` | `true` unless `APP_ENV=prod` | shows the detailed error page |

## Architecture

```
src/
├── components/
│   ├── Container      dependency injection container, autowiring, providers
│   ├── Controller     controller resolution and AbstractController
│   ├── Exception      FrameworkException and error pages
│   ├── Kernel         boot and request lifecycle
│   ├── Routing        YAML routes, matching, URL generation
│   └── View           PHP templates, layouts, sections, helpers
└── packages/
    └── Yaml           YAML parser
```

Each feature follows the same layout:

```
Feature/FeatureManager.php
Feature/Provider/FeatureProvider.php
Feature/Contract/FeatureInterface.php
Feature/Contract/AbstractFeature.php
```

## Changes

- Initial version: routes in YAML, YAML parser, PHP views, controllers, container and centralized exceptions.