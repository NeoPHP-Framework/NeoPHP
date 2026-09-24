# NeoPHP v1.x

## Overview

NeoPHP v1.x is the base of the framework. It has no dependency other than PHP 8.2. It provides:

- routes defined in `config/routes.yaml`
- a YAML parser
- PHP views stored in `templates/`
- an HTTP layer (`Request`, `Response`, `JsonResponse`, `RedirectResponse`)
- a configuration loaded from `.env` files and `config/**/*.yaml`, with placeholders (`%kernel.root_path%`, `%env(APP_NAME)%`...)
- a console (`php bin/neo`) that generates the project files

## Installation (development)

Until the framework is published on Packagist, require it through a Composer path repository (symlink). Create a folder next to the framework with this `composer.json`:

```json
{
    "name": "neophp/test",
    "type": "project",
    "repositories": [
        { "type": "path", "url": "../neophp", "options": { "symlink": true } }
    ],
    "require": {
        "php": ">=8.2",
        "neophp/framework": "*@dev"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "scripts": {
        "neo:install": "@php vendor/bin/neo install",
        "post-create-project-cmd": "@neo:install",
        "post-install-cmd": "@neo:install",
        "post-update-cmd": "@neo:install"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Then:

```bash
composer install
php bin/neo serve
```

`composer install` runs `neo install` automatically: it generates the project files (including `bin/neo`). Existing files are never overwritten, unless `php bin/neo install --force` is used. A generated file that was deleted is created again on the next `composer install` or `composer update`.

`neo install` generates the project files and adds the `App\` autoload to `composer.json`. Existing files are never overwritten, unless `--force` is given.

```
.env
.gitignore
assets/
config/routes.yaml
config/framework/app.yaml
config/packages/
public/.htaccess
public/index.php
public/builds/
src/Kernel.php
src/Controller/HomeController.php
src/Command/  src/Event/  src/Listener/  src/Middleware/  src/Service/
templates/base.php
templates/home/index.php
tests/
```

## Console

| Command | Description |
|---|---|
| `php bin/neo` | lists the commands |
| `php bin/neo install [--force]` | generates the project files |
| `php bin/neo serve [--host=127.0.0.1] [--port=8000]` | starts the PHP development server |
| `php bin/neo route:list` | lists the routes |

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
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;

class UserController extends AbstractController
{
    public function show(int $id, Request $request): Response
    {
        if ($id > 100) {
            throw $this->createNotFoundException('User {id} not found.', ['id' => $id]);
        }

        return $this->render('user/show', ['id' => $id, 'tab' => $request->query->get('tab')]);
    }
}
```

Controller arguments are resolved from, in order: the `Request` (type-hint), the route parameters (cast to `int`, `float`, `bool` or `string`), the request attributes, the container (class type-hints), the default values.

A controller returns a `Response`. For convenience, a `string` becomes an HTML response, an `array` a JSON response and `null` a 204 response.

`AbstractController` shortcuts:

| Method | Returns |
|---|---|
| `render($template, $parameters, $status, $headers)` | `Response` |
| `renderView($template, $parameters)` | `string` |
| `json($data, $status, $headers)` | `JsonResponse` |
| `redirect($url, $status)` | `RedirectResponse` |
| `redirectToRoute($route, $parameters, $status)` | `RedirectResponse` |
| `generateUrl($route, $parameters)` | `string` |
| `createNotFoundException($message, $context)` | `NotFoundHttpException` (404) |
| `createAccessDeniedException($message, $context)` | `AccessDeniedHttpException` (403) |
| `get($id)` / `has($id)` | container access |

## HTTP

`Request`

| Property / method | Description |
|---|---|
| `$request->query` | GET parameters |
| `$request->request` | POST parameters (and JSON body for POST, PUT, PATCH, DELETE) |
| `$request->attributes` | route parameters, `_route`, `_controller` |
| `$request->cookies`, `$request->files`, `$request->server`, `$request->headers` | cookies, uploaded files, server, headers |
| `getMethod()` | HTTP method (a POST form can send `_method` with `PUT`, `PATCH` or `DELETE`) |
| `getPath()`, `getUri()`, `getHost()`, `getScheme()`, `isSecure()` | URL information |
| `getContent()`, `toArray()` | raw body, JSON body |
| `isJson()`, `wantsJson()`, `isXmlHttpRequest()` | content negotiation |

Every bag provides `all()`, `get()`, `has()`, `set()`, `remove()`, `getString()`, `getInt()`, `getBoolean()`.

`Response`

```php
$response = new Response('<h1>Hello</h1>', 200, ['X-Custom' => 'value']);
$response->setStatusCode(201);
$response->setHeader('Cache-Control', 'no-cache');
$response->setCookie('theme', 'dark', time() + 3600);

new JsonResponse(['ok' => true]);
new RedirectResponse('/login');
```

HTTP exceptions (all extending `FrameworkException`): `HttpException($status, $message, $headers)`, `NotFoundHttpException`, `AccessDeniedHttpException`, `BadRequestHttpException`.

Errors are rendered as HTML, or as JSON when the request sends `Accept: application/json`.

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
| `$this->config('framework.app.name')` | reads a configuration value |

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

`{placeholders}` in the message are replaced by the context values. The status code (500 by default) and the headers (`getHeaders()`) are used for the HTTP response: routing exceptions use 404 and 405 (with the `Allow` header).

## Configuration

### Environment files

The kernel loads, in this order (later files override earlier ones):

| File | Committed | Purpose |
|---|---|---|
| `.env` | yes | default values |
| `.env.local` | no | local overrides (not loaded when `APP_ENV=test`) |
| `.env.{APP_ENV}` | yes | values for one environment (`.env.prod`, `.env.test`...) |
| `.env.{APP_ENV}.local` | no | local overrides for one environment |

Real environment variables (server, Docker...) always win over the files.

```dotenv
APP_NAME="My application"
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://${DB_USER}@localhost/app"
```

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `dev` | environment name |
| `APP_DEBUG` | `true` unless `APP_ENV=prod` | shows the detailed error page |

### YAML configuration

Every `*.yaml` file of `config/` is loaded, except `routes.yaml` and `config/routes/`. The key is the file path:

| File | Key |
|---|---|
| `config/framework/app.yaml` | `framework.app` |
| `config/packages/mail.yaml` | `packages.mail` |
| `config/services.yaml` | `services` |

```php
use NeoPHP\Component\Config\Contract\ConfigInterface;

public function show(ConfigInterface $config): Response
{
    $name = $config->get('framework.app.name');
    $port = $config->get('packages.mail.port', 25);
}
```

In a template: `<?= $this->e($this->config('framework.app.name')) ?>`.

### Placeholders

Placeholders can be used in every YAML file, `routes.yaml` included:

```yaml
host: '%env(MAIL_HOST)%'
port: '%env(int:MAIL_PORT)%'
secure: '%env(bool:MAIL_SECURE)%'
from: 'noreply@%env(MAIL_HOST)%'
templates: '%kernel.templates_path%/emails'
app_name: '%framework.app.name%'
discount: '10%%'
```

| Placeholder | Value |
|---|---|
| `%env(NAME)%` | environment variable (string) |
| `%env(int:NAME)%`, `%env(float:NAME)%`, `%env(bool:NAME)%` | environment variable cast to a type |
| `%env(json:NAME)%`, `%env(csv:NAME)%` | environment variable decoded as JSON / split on commas |
| `%kernel.root_path%` | project root directory |
| `%kernel.config_path%` | `config/` directory |
| `%kernel.public_path%` | `public/` directory |
| `%kernel.templates_path%` | `templates/` directory |
| `%kernel.environment%` | `APP_ENV` |
| `%kernel.debug%` | debug mode (bool) |
| `%kernel.version%` | NeoPHP version |
| `%any.config.key%` | value of another configuration key |
| `%%` | a literal `%` |

A value made of a single placeholder keeps its type (`'%kernel.debug%'` is a bool). An undefined environment variable or configuration key throws a `ConfigException`.
## Architecture

```
src/
├── components/
│   ├── Config         YAML configuration and placeholders
│   ├── Container      dependency injection container, autowiring, providers
│   ├── Controller     controller resolution and AbstractController
│   ├── Exception      FrameworkException and error pages
│   ├── Http           Request, Response, JsonResponse, RedirectResponse
│   ├── Kernel         boot and request lifecycle
│   ├── Routing        YAML routes, matching, URL generation
│   └── View           PHP templates, layouts, sections, helpers
├── packages/
│   └── Yaml           YAML parser
└── process/
    ├── Console        neo command line (install, serve, route:list)
    └── Installer      project skeleton generation
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
- HTTP layer: `Request`, `Response`, `JsonResponse`, `RedirectResponse`, HTTP exceptions, JSON errors.
- Console `neo` with the `install`, `serve` and `route:list` commands.
- `bin/neo` generated in the project by `neo install`.
- Configuration: `.env` files, `config/**/*.yaml`, placeholders `%kernel.*%`, `%env(...)%` and `%config.key%`.