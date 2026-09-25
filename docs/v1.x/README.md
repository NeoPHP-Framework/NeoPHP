# NeoPHP v1.x

## Overview

NeoPHP v1.x is the base of the framework. It has no dependency other than PHP 8.2. It provides:

- routes defined in `config/routes.yaml` or with the `#[Route]` attribute, cached in `var/cache/`
- a YAML parser
- views stored in `templates/`: PHP templates, and Twig templates when `twig/twig` is installed
- view helpers shared by every template engine, shipped by each feature in `Helper/View/`
- assets compiled from `assets/` to `public/builds/` with hashed file names and a manifest
- an HTTP layer (`Request`, `Response`, `JsonResponse`, `RedirectResponse`)
- sessions, cookies (optionally signed) and flash messages, configured in `config/framework/app.yaml`
- middlewares (PSR-15 style), global or attached to routes and controllers
- a dependency injection container with autowiring, `#[Autowire]`, `#[Inject]` and `config/services.yaml`
- a configuration loaded from `.env` files and `config/**/*.yaml`, with placeholders (`%kernel.root_path%`, `%env(APP_NAME)%`...)
- events and listeners (PSR-14 style), with the kernel events
- a validator with constraints usable as attributes (`#[Assert\NotBlank]`) or objects (`new NotBlank()`)
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
assets/css/app.css
config/routes.yaml
config/services.yaml
config/framework/app.yaml
config/framework/asset.yaml
config/framework/event.yaml
config/framework/logger.yaml
config/framework/middleware.yaml
config/framework/view.yaml
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
var/cache/
var/log/
var/sessions/
```

`neo install` also writes a random `APP_SECRET` in `.env`.

## Console

| Command | Description |
|---|---|
| `php bin/neo` | lists the commands |
| `php bin/neo install [--force]` | generates the project files |
| `php bin/neo serve [--host=127.0.0.1] [--port=8000]` | starts the PHP development server |
| `php bin/neo route:list` | lists the routes |
| `php bin/neo middleware:list` | lists the global middlewares, the aliases and the groups |
| `php bin/neo service:list [filter]` | lists the services, the aliases and the interfaces bound automatically |
| `php bin/neo event:list [filter]` | lists the events and their listeners in the order they are called |
| `php bin/neo cache:clear` | clears `var/cache/` (routes, Twig templates...) |
| `php bin/neo asset:reload [--minify]` | compiles `assets/` into `public/builds/` and rebuilds the manifest |

Each feature can ship its own commands in `Feature/Helper/Console/`: they are discovered automatically, in the framework and in the application (`src/**/Helper/Console/`). A command extends `NeoPHP\Process\Console\Contract\AbstractCommand`.

## Routes

Routes are declared in `config/routes.yaml`, with the `#[Route]` attribute on the controllers, or both.

### Attributes

`config/routes.yaml` declares where the controllers are:

```yaml
controllers:
  resource: ../src/Controller/
  type: attribute
```

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('blog/index');
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => '[a-z0-9-]+'])]
    #[Route('/post/{slug}', name: 'show_legacy')]
    public function show(string $slug): Response
    {
        return $this->render('blog/show', ['slug' => $slug]);
    }
}
```

| Argument | Description |
|---|---|
| `path` | URL pattern, placeholders written `{name}` |
| `name` | route name (default: built from the class and the method, `app_blog_show`) |
| `methods` | allowed HTTP methods: `['GET', 'POST']` or `'GET\|POST'` (all when omitted) |
| `requirements` | regex per placeholder |
| `defaults` | default values |
| `options` | free options |
| `middlewares` | middlewares of the route (see [Middlewares](#middlewares)) |

On the class, `#[Route]` is a prefix: its `path` and `name` are prepended to every route of the class, its `middlewares` run before the ones of the method, and its `methods`, `requirements`, `defaults` and `options` are the default values of these routes. On an invokable class (`__invoke()`) without method routes, the class attribute defines the route itself. The attribute is repeatable.

`resource` can be a directory (scanned recursively) or a PHP file. `prefix`, `name_prefix`, `requirements`, `defaults`, `options`, `methods` and `middlewares` can be used on the import, like for a YAML import:

```yaml
admin_controllers:
  resource: ../src/Admin/Controller/
  type: attribute
  prefix: /admin
  name_prefix: admin_
```

### YAML

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
| `middlewares` | middlewares of the route; on an import, they run before the ones of the imported routes |
| `resource` | imports another routes file or a controllers directory (relative to the current file) |
| `type` | `yaml` or `attribute` (default: `attribute` for a directory or a `.php` file, `yaml` otherwise) |
| `prefix` / `name_prefix` | prefix applied to the imported paths / names |

A route name must be unique: a name defined twice (in YAML, in attributes, or both) throws a `RoutingException` that gives both locations.

A path that matches no route returns a 404. A path that matches with the wrong HTTP method returns a 405. Routes are tested in the order they are declared.

### Cache

Routes are compiled into `var/cache/routing/routes.{env}.php`.

| Mode | Behavior |
|---|---|
| debug | the cache is rebuilt when a routes file, a controller, a `.env` file or the installed packages change |
| production | the cache is built once, on the first request, and never checked again |

In production, run `php bin/neo cache:clear` on every deployment.

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
| `getSession()` | `SessionInterface` |
| `getCookies()` | `CookieInterface` |
| `addFlash($type, $message)` | adds a flash message |
| `dispatch($event)` | dispatches an event (see [Events](#events)) |
| `validate($value, $constraints, $groups)` | `ViolationList` (see [Validator](#validator)) |

`AbstractController` has no method of its own: it is made of traits, and each feature ships its trait in `Feature/Helper/Controller/`:

| Trait | Methods |
|---|---|
| `Container/Helper/Controller/ContainerController` | `setContainer()`, `get()`, `has()` |
| `Cookie/Helper/Controller/CookieController` | `getCookies()` |
| `Event/Helper/Controller/EventController` | `dispatch()` |
| `Flash/Helper/Controller/FlashController` | `addFlash()` |
| `Http/Helper/Controller/HttpController` | `json()`, `redirect()`, `createNotFoundException()`, `createAccessDeniedException()` |
| `Routing/Helper/Controller/RoutingController` | `generateUrl()`, `redirectToRoute()` |
| `Session/Helper/Controller/SessionController` | `getSession()` |
| `Validator/Helper/Controller/ValidatorController` | `validate()` |
| `View/Helper/Controller/ViewController` | `render()`, `renderView()` |

```php
abstract class AbstractController implements ControllerInterface
{
    use ContainerController;
    use CookieController;
    use EventController;
    use FlashController;
    use HttpController;
    use RoutingController;
    use SessionController;
    use ValidatorController;
    use ViewController;
}
```

A controller can also pick only the traits it needs. `ContainerController` is required: the other traits get their services through `get()`.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use NeoPHP\Component\Container\Helper\Controller\ContainerController;
use NeoPHP\Component\Controller\Contract\ControllerInterface;
use NeoPHP\Component\Http\Helper\Controller\HttpController;
use NeoPHP\Component\Http\Response\JsonResponse;

class ApiController implements ControllerInterface
{
    use ContainerController;
    use HttpController;

    public function status(): JsonResponse
    {
        return $this->json(['ok' => true]);
    }
}
```

An application trait follows the same rule: it declares `abstract protected function get(string $id): mixed;` and uses `$this->get()` to reach its services.

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

Templates live in `templates/`. The extension selects the engine:

| Extension | Engine |
|---|---|
| `.php` | built-in PHP engine |
| `.html.twig`, `.twig` | Twig, only when `twig/twig` is installed (`composer require twig/twig`) |

`render('user/show')` looks for `user/show.php`, then `user/show.html.twig`, then `user/show.twig`. Rendering a `.twig` template without Twig installed throws an explicit error.

### PHP templates

Inside a PHP template, `$this` gives access to:

| Method | Description |
|---|---|
| `$this->extend('base')` | renders the template inside the `base` layout |
| `$this->start('name')` / `$this->stop()` | captures a section |
| `$this->section('name')` | outputs a section in a layout |
| `$this->include('partials/menu', [...])` | renders a partial |
| `$this->e($value)` | escapes a value for HTML |
| `$this->filter('name', $value, ...)` | applies a view filter |
| `$this->path('route', [...])` | generates the URL of a route |
| `$this->asset('css/app.css')` | URL of a compiled asset (see [Assets](#assets)) |
| `$this->config('framework.app.name')` | reads a configuration value |
| `$this->flashes('success')` | reads and removes flash messages (see [Flash messages](#flash-messages)) |
| `$this->session('user_id')` | reads a session value |
| `$this->cookie('theme', 'light')` | reads a cookie |

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

### Twig templates

`templates/base.html.twig`

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}{{ config('framework.app.name') }}{% endblock %}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
{% block body %}{% endblock %}
</body>
</html>
```

`templates/user/show.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block body %}
<h1>User #{{ id }}</h1>
<a href="{{ path('home') }}">Home</a>
{% endblock %}
```

Twig errors are wrapped in a `ViewException`.

### View helpers

A view helper works with every engine and never imports Twig. Its interfaces define what it provides:

| Interface | Result |
|---|---|
| `ViewFunctionInterface` | function: `{{ name(...) }}` / `$this->name(...)` |
| `ViewFilterInterface` | filter: `{{ value\|name }}` / `$this->filter('name', $value)` |
| `ViewGlobalInterface` | global variable (`getValue()`): `{{ name }}` / `$name` |
| `ViewSafeHtmlInterface` | the output is not escaped |

`getName()` gives the name used in templates. Functions and filters are called through `__invoke()`.

Each feature ships its own helpers in `Feature/Helper/View/FeatureViewHelper.php`:

| Helper | Name |
|---|---|
| `Asset/Helper/View/AssetViewHelper.php` | `asset()` |
| `Config/Helper/View/ConfigViewHelper.php` | `config()` |
| `Cookie/Helper/View/CookieViewHelper.php` | `cookie()` |
| `Flash/Helper/View/FlashesViewHelper.php` | `flashes()` |
| `Routing/Helper/View/PathViewHelper.php` | `path()` |
| `Session/Helper/View/SessionViewHelper.php` | `session()` |

The View component contains no helper: it discovers every `Helper/View/` directory of the framework and of the application (`src/**/Helper/View/`). Dependencies are autowired. An application helper with the same name as a framework helper replaces it.

`src/Shop/Helper/View/PriceViewHelper.php`

```php
<?php

declare(strict_types=1);

namespace App\Shop\Helper\View;

use NeoPHP\Component\View\Contract\ViewFilterInterface;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class PriceViewHelper implements ViewFunctionInterface, ViewFilterInterface
{
    public function getName(): string
    {
        return 'price';
    }

    public function __invoke(float $amount, string $currency = '€'): string
    {
        return number_format($amount, 2, ',', ' ') . ' ' . $currency;
    }
}
```

```twig
{{ price(12.5) }}  {{ amount|price('$') }}
```

```php
<?= $this->price(12.5) ?>  <?= $this->filter('price', $amount, '$') ?>
```

Helpers stored outside a `Helper/View/` directory can be listed under `helpers` in `config/framework/view.yaml`.

### Configuration

`config/framework/view.yaml`

```yaml
paths:
  - '%kernel.templates_path%'

namespaces:
  admin: '%kernel.root_path%/templates/admin'

helpers: []

twig:
  enabled: true
  cache: '%kernel.root_path%/var/cache/twig'
  auto_reload: true
  strict_variables: '%kernel.debug%'
```

| Option | Description |
|---|---|
| `paths` | template directories |
| `namespaces` | named directories: `@admin/dashboard` renders `templates/admin/dashboard.*` |
| `helpers` | additional helper classes |
| `twig.enabled` | disables Twig even when it is installed |
| `twig.*` | Twig options: `cache`, `debug`, `auto_reload`, `strict_variables`, `autoescape`, `charset` |

## Assets

Assets are stored in `assets/` and compiled into `public/builds/`, with the same structure. A hash of the content is added to every file name: `{filename}-{hash}.{extension}`.

```
assets/css/app.css        ->  public/builds/css/app-3f2a9c1b.css
assets/img/logo.png       ->  public/builds/img/logo-d07ec8c2.png
assets/js/app.js          ->  public/builds/js/app-8801909f.js
```

`public/builds/manifest.json` maps each asset to its compiled URL:

```json
{
    "css/app.css": "/builds/css/app-3f2a9c1b.css",
    "img/logo.png": "/builds/img/logo-d07ec8c2.png"
}
```

In a template, `asset()` returns the compiled URL:

```twig
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<img src="{{ asset('img/logo.png') }}" alt="">
```

```php
<link rel="stylesheet" href="<?= $this->e($this->asset('css/app.css')) ?>">
```

| Mode | Behavior of `asset()` |
|---|---|
| debug (`auto_compile: true`) | compiles the asset again when its content changed, updates the manifest and removes the previous build |
| production (`auto_compile: false`) | reads the manifest; an asset missing from the manifest is compiled once |

`php bin/neo asset:reload` empties `public/builds/`, compiles every file of `assets/` and rebuilds the manifest. Run it on every deployment. `--minify` also minifies CSS (comments and whitespace) and JavaScript (comments and indentation, line breaks are kept).

In CSS files, `url(...)` and `@import` pointing to another file of `assets/` are rewritten to the compiled URL (`url('../img/logo.png')` becomes `url('/builds/img/logo-d07ec8c2.png')`). External URLs, absolute paths and files outside `assets/` are kept as is. Absolute URLs given to `asset()` (`https://...`, `//...`) are returned unchanged.

`config/framework/asset.yaml`

```yaml
source_path: '%kernel.root_path%/assets'
build_path: '%kernel.public_path%/builds'
public_url: /builds
auto_compile: '%kernel.debug%'

hash:
  algorithm: xxh128
  length: 8
```

| Option | Description |
|---|---|
| `source_path` | directory of the assets |
| `build_path` | directory of the compiled files and of `manifest.json` |
| `public_url` | URL prefix of the compiled files (a CDN URL can be used) |
| `auto_compile` | compiles the changed assets on each request |
| `hash.algorithm` / `hash.length` | hash function (any `hash_algos()` value) and number of characters kept |

In PHP code, `NeoPHP\Component\Asset\Contract\AssetInterface` provides `url()`, `compile()`, `reload()` and `clear()`. A custom compiler implements `CompilerInterface` and is registered with `addCompiler()`.

## Middlewares

A middleware runs before and after the controller. It can modify the request, return a response without calling the controller, or modify the response.

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Middleware\Attribute\AsMiddleware;
use NeoPHP\Component\Middleware\Contract\MiddlewareInterface;
use NeoPHP\Component\Middleware\Contract\RequestHandlerInterface;
use NeoPHP\Component\Session\Contract\SessionInterface;

#[AsMiddleware(name: 'auth')]
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(protected SessionInterface $session)
    {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->session->has('user_id')) {
            return new RedirectResponse('/login');
        }

        $response = $handler->handle($request);
        $response->setHeader('Cache-Control', 'no-store');

        return $response;
    }
}
```

The interfaces mirror PSR-15 without dependency: `MiddlewareInterface::process(Request, RequestHandlerInterface): Response` and `RequestHandlerInterface::handle(Request): Response`. Middlewares are built by the container: their dependencies are autowired.

### Global middlewares

Global middlewares run on every request, before the routing (they also run for a 404).

`config/framework/middleware.yaml`

```yaml
global:
  - App\Middleware\MaintenanceMiddleware

aliases:
  auth: App\Middleware\AuthMiddleware

groups:
  admin: [auth, App\Middleware\AdminMiddleware]
```

A middleware can also declare itself with `#[AsMiddleware]`, discovered in `src/`:

| Argument | Description |
|---|---|
| `name` | alias of the middleware (`auth`) |
| `global` | runs the middleware on every request |
| `priority` | order of the global middlewares declared with the attribute (highest first) |

The global middlewares of `middleware.yaml` run first, in their order, then the global middlewares declared with `#[AsMiddleware]`. The discovery is cached in `var/cache/middleware/` (rebuilt in debug when a file of `src/` changes).

### Route middlewares

A middleware is referenced by its alias, a group or its class name.

```php
#[Middleware('auth')]
class AdminController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users', middlewares: ['audit'])]
    #[Middleware('admin', App\Middleware\TwoFactorMiddleware::class)]
    public function users(): Response
    {
        return $this->render('admin/users');
    }
}
```

```yaml
admin:
  resource: ../src/Admin/Controller/
  type: attribute
  prefix: /admin
  middlewares: [admin]

legacy:
  path: /legacy
  controller: App\Controller\LegacyController::index
  middlewares: [auth]
```

Order of execution:

1. global middlewares of `middleware.yaml`
2. global middlewares declared with `#[AsMiddleware(global: true)]`
3. `middlewares` of the YAML import, then of the YAML route
4. `middlewares` of `#[Route]` on the class, then on the method
5. `#[Middleware]` on the class, then on the method
6. the controller

A middleware is never run twice: a middleware already global is ignored on the route. An unknown alias throws a `MiddlewareException`.

## Services

Every class can be injected: the container reads the constructor and gives each parameter the service of its type (autowiring). Controllers, middlewares, commands and view helpers are built this way.

```php
class NewsletterController extends AbstractController
{
    public function __construct(protected MailerInterface $mailer)
    {
    }
}
```

A service is shared: the same instance is given everywhere during the request. `#[Autowire(shared: false)]` on the class, or `shared: false` in `services.yaml`, creates a new instance each time.

### #[Autowire]

On a parameter, `#[Autowire]` says what to inject when the type is not enough:

```php
use NeoPHP\Component\Container\Attribute\Autowire;

class SmtpMailer implements MailerInterface
{
    public function __construct(
        #[Autowire(service: 'mailer.transport')] protected TransportInterface $transport,
        #[Autowire(config: 'framework.app.name')] protected string $appName,
        #[Autowire(env: 'MAILER_DSN')] protected string $dsn,
        #[Autowire(param: 'kernel.debug')] protected bool $debug,
        #[Autowire('%kernel.root_path%/var/mails')] protected string $spool,
    ) {
    }
}
```

| Argument | Injected value |
|---|---|
| `service` | the service with this id |
| `config` | a configuration value (`framework.app.name`) |
| `env` | an environment variable |
| `param` | a kernel parameter (`kernel.debug`, `kernel.root_path`...) |
| `value` (first argument) | a value; placeholders (`%env(...)%`, `%kernel.*%`, `%config.key%`) are resolved |
| `shared` | on the class only: `false` creates a new instance each time |

A missing service, configuration key or environment variable throws a `ContainerException`, unless the parameter is nullable (it then receives `null`).

### #[Inject]

On a property, `#[Inject]` injects the value after the constructor. Without argument, the type of the property is used. It accepts the same arguments as `#[Autowire]` (`service`, `config`, `env`, `param`, `value`).

```php
use NeoPHP\Component\Container\Attribute\Inject;

class ReportService
{
    #[Inject]
    protected LoggerInterface $logger;

    #[Inject(config: 'framework.app.name')]
    protected string $appName;
}
```

### config/services.yaml

```yaml
services:
  _defaults:
    shared: true

  App\:
    resource: ../src/
    exclude:
      - ../src/Kernel.php

  App\Service\SmtpMailer:
    arguments:
      $host: '%env(MAIL_HOST)%'
      $logger: '@NeoPHP\Component\Logger\Contract\LoggerInterface'
    calls:
      - [setFrom, ['noreply@example.com']]

  mailer: '@App\Service\SmtpMailer'

  App\Service\NotifierInterface: '@App\Service\SmsNotifier'

  app.api_client:
    class: App\Service\ApiClient
    factory: ['@App\Service\ApiClientFactory', 'create']
    arguments:
      $baseUrl: 'https://api.example.com'
    shared: false
```

| Entry | Description |
|---|---|
| `_defaults.shared` | default value of `shared` for the file |
| `Namespace\: { resource, exclude, shared }` | registers every class of a directory (`exclude`: files, directories or `*` patterns, relative to the file) |
| `id: ~` | registers a class with its default values |
| `id: '@other'` / `id: { alias: other }` | alias of another service |
| `class` | class of the service (default: the id) |
| `arguments` | arguments by name (`$host`) or by position; the others are autowired |
| `calls` | methods called after the construction: `[method, [arguments]]` |
| `factory` | `['@service', 'method']`, `['Class', 'method']` or `'Class::method'` |
| `shared` | `false` creates a new instance each time |

In arguments, `@id` is a service, `@?id` a service or `null` when it does not exist, `@@text` the string `@text`, and `%...%` the placeholders of the configuration (resolved when the service is built).

When one class of the file implements an interface, the interface is bound to this class automatically: a parameter typed `MailerInterface` receives `SmtpMailer`. When several classes implement it, resolving the interface throws a `ServiceException` that asks for an alias. An interface already bound by the framework is never replaced automatically.

`services.yaml` is compiled into `var/cache/service/services.{env}.php`, rebuilt in debug when the file or a class of a `resource` changes. In production, run `php bin/neo cache:clear` on every deployment.

## Events

An event is an object. Listeners are called with it, by order of priority.

```php
<?php

declare(strict_types=1);

namespace App\Event;

use NeoPHP\Component\Event\Contract\AbstractEvent;

class UserRegisteredEvent extends AbstractEvent
{
    public function __construct(public string $email)
    {
    }
}
```

```php
$this->dispatch(new UserRegisteredEvent($email));
```

Outside a controller, inject `NeoPHP\Component\Event\Contract\EventDispatcherInterface` and call `dispatch($event)`: it returns the event, so a listener can fill it with data.

The interfaces mirror PSR-14 without dependency. An event extending `AbstractEvent` (or implementing `StoppableEventInterface`) can be stopped: `$event->stopPropagation()` prevents the next listeners from being called.

### Listeners

`#[AsListener]` on a class (method `__invoke()`) or on public methods. The event is the type of the first parameter:

```php
<?php

declare(strict_types=1);

namespace App\Listener;

use App\Event\UserRegisteredEvent;
use NeoPHP\Component\Event\Attribute\AsListener;

#[AsListener]
class SendWelcomeMail
{
    public function __construct(protected MailerInterface $mailer)
    {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->mailer->send($event->email);
    }
}

class AuditListener
{
    #[AsListener(priority: 100)]
    public function onRegistered(UserRegisteredEvent $event): void
    {
    }
}
```

| Argument | Description |
|---|---|
| `event` | event class (default: type of the first parameter) |
| `method` | on a class: method to call (default: `__invoke`) |
| `priority` | highest first (default `0`); listeners with the same priority are called in their declaration order |

A listener can also listen to a parent class or an interface: it is then called for every event that extends it.

A subscriber lists several events:

```php
use NeoPHP\Component\Event\Contract\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::class => 'onRegistered',
            UserDeletedEvent::class => ['onDeleted', 10],
            PasswordChangedEvent::class => [['notify', 10], ['log']],
        ];
    }
}
```

Listeners and subscribers are discovered in `src/` and in the framework (`Feature/Helper/Listener/`), built by the container (their dependencies are autowired) and instantiated only when their event is dispatched. The discovery is cached in `var/cache/event/`.

They can also be declared in `config/framework/event.yaml`:

```yaml
listeners:
  App\Event\UserRegisteredEvent:
    - App\Listener\SendWelcomeMail
    -   listener: App\Listener\Audit
        method: onRegistered
        priority: 10

subscribers:
  - App\Subscriber\UserSubscriber
```

### Kernel events

| Event | When | Usage |
|---|---|---|
| `RequestEvent` | before the global middlewares | `setResponse()` answers without routing (maintenance...) |
| `ControllerEvent` | after the route middlewares, before the controller | `setController()`, `setParameters()` |
| `ResponseEvent` | for every response, errors included | modifies or replaces the response |
| `ExceptionEvent` | when an exception is thrown | `setResponse()` replaces the error page, `setThrowable()` |
| `TerminateEvent` | after the response is sent | slow work (mails, logs) |

They are in `NeoPHP\Component\Kernel\Event\` and give access to `getKernel()` and `getRequest()`. The framework uses them too: the queued cookies are added and the session is saved by listeners of `ResponseEvent` (`Cookie/Helper/Listener/`, `Session/Helper/Listener/`).

## Validator

Constraints are classes: the same class is used as an attribute or as an object.

```php
<?php

declare(strict_types=1);

namespace App\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class SignupDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 20)]
    public ?string $username = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Length(min: 8)]
    public ?string $password = null;

    #[Assert\EqualTo(propertyPath: 'password', message: 'The passwords do not match.')]
    public ?string $confirm = null;

    #[Assert\Valid]
    public ?AddressDto $address = null;
}
```

```php
$violations = $this->validate($dto);

if (count($violations) > 0) {
    return $this->json(['errors' => $violations->toArray()], 422);
}
```

Outside a controller, inject `NeoPHP\Component\Validator\Contract\ValidatorInterface`.

| Call | Validates |
|---|---|
| `validate($object)` | the constraints declared with attributes on the object (properties and class) |
| `validate($value, new Email())` | a value with one constraint |
| `validate($value, [new NotBlank(), new Length(min: 3)])` | a value with several constraints |
| `validate($data, ['email' => [new NotBlank(), new Email()], 'age' => new Range(min: 18)])` | an array (or object) field by field; a missing field is `null` |
| `validateProperty($object, 'email')` | one property of an object |
| `validateOrFail(...)` | same as `validate()`, throws a `ValidationFailedException` (HTTP 422) when it fails |

### Violations

`validate()` returns a `ViolationList`:

| Method | Returns |
|---|---|
| `count($violations)` | number of violations |
| `has()` / `has('email')` | whether there is a violation (for a path) |
| `first()` / `first('email')` | first message |
| `messages('email')` | messages of a path |
| `get('email')` | `Violation` objects of a path (`getMessage()`, `getPropertyPath()`, `getInvalidValue()`, `getParameters()`, `getConstraint()`) |
| `toArray()` | `['email' => ['This value is not a valid email address.'], ...]` |

Paths: `email`, `address.city` (with `Valid`), `tags[1]` (with `All`), `data[email]` (with `Collection`).

For a request that expects JSON, an uncaught `ValidationFailedException` returns a 422 response with the violations:

```json
{"error": {"status": 422, "message": "The data is not valid: 1 violation(s).", "violations": {"username": ["This value should not be blank."]}}}
```

### Constraints

| Constraint | Options |
|---|---|
| `NotBlank` | `allowNull`, `trim` (blank: `null`, `''`, `[]`, `false`) |
| `Blank`, `NotNull`, `IsNull`, `IsTrue`, `IsFalse` | |
| `Type` | `type` (`string`, `int`, `float`, `bool`, `array`, `numeric`, `scalar`, `iterable`, `callable`, `object`, `alpha`, `digit`, `alnum` or a class; several allowed) |
| `Length` | `exactly`, `min`, `max` (characters, UTF-8) |
| `Count` | `exactly`, `min`, `max` (elements of an array or `Countable`) |
| `Range` | `min`, `max` (numbers or dates) |
| `EqualTo`, `NotEqualTo`, `GreaterThan`, `GreaterThanOrEqual`, `LessThan`, `LessThanOrEqual` | `value` or `propertyPath` (compares with another property: `propertyPath: 'password'`) |
| `Positive`, `PositiveOrZero`, `Negative`, `NegativeOrZero` | |
| `Email`, `Uuid`, `Json`, `Date` (`Y-m-d`) | |
| `Url` | `protocols` (default `['http', 'https']`) |
| `Ip` | `version` (`4`, `6` or `all`) |
| `DateTime` | `format` (default `Y-m-d H:i:s`) |
| `Regex` | `pattern`, `match` (`false`: the value must not match) |
| `Choice` | `choices` or `callback`, `multiple`, `min`, `max`, `strict` |
| `All` | `constraints` applied to each element |
| `Collection` | `fields` (`['email' => [...]]`), `allowExtraFields`, `allowMissingFields` |
| `Valid` | validates the nested object (or each object of an array) |
| `Callback` | `callback`: method of the object or callable |

Every constraint accepts `message` (or `minMessage`, `maxMessage`...) and `groups`. Messages use placeholders: `{{ value }}`, `{{ limit }}`, `{{ min }}`, `{{ max }}`, `{{ compared_value }}`, `{{ choices }}`, `{{ type }}`. Except `NotBlank`, `NotNull` and `IsNull`, constraints accept `null` and `''`: add `NotBlank` to make a value required.

### Groups

A constraint belongs to the `Default` group, unless `groups` is given. `validate()` validates the `Default` group, unless groups are given:

```php
#[Assert\NotBlank(groups: ['create'])]
public ?string $password = null;
```

```php
$this->validate($dto, null, ['Default', 'create']);
```

### Callback

```php
#[Assert\Callback('validatePeriod')]
class BookingDto
{
    public ?\DateTimeImmutable $start = null;

    public ?\DateTimeImmutable $end = null;

    public function validatePeriod(ExecutionContext $context): void
    {
        if ($this->start !== null && $this->end !== null && $this->end < $this->start) {
            $context->addViolation('The end must be after the start.', [], 'end');
        }
    }
}
```

On a property, the method receives `($value, ExecutionContext $context)`. `new Callback(fn ($value, $context) => ...)` works with a closure.

### Custom constraints

A constraint extends `AbstractConstraint`. Its validator is the class with the same name followed by `Validator` (override `validatedBy()` to change it). The validator is built by the container: its dependencies are autowired.

```php
<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use NeoPHP\Component\Validator\Contract\AbstractConstraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class UniqueUsername extends AbstractConstraint
{
    public string $message = 'The username {{ value }} is already used.';
}
```

```php
<?php

declare(strict_types=1);

namespace App\Validator;

use NeoPHP\Component\Validator\Context\ExecutionContext;
use NeoPHP\Component\Validator\Contract\AbstractConstraintValidator;
use NeoPHP\Component\Validator\Contract\ConstraintInterface;

class UniqueUsernameValidator extends AbstractConstraintValidator
{
    public function __construct(protected UserRepository $users)
    {
    }

    public function validate(mixed $value, ConstraintInterface $constraint, ExecutionContext $context): void
    {
        if ($this->isEmpty($value)) {
            return;
        }

        if ($this->users->existsByUsername((string) $value)) {
            $context->addViolation($constraint->message, ['value' => static::formatValue($value)]);
        }
    }
}
```

`ExecutionContext` gives `addViolation($message, $parameters, $path)`, `getObject()` (object being validated), `getRoot()`, `getPropertyPath()`, `getGroups()` and `validate($value, $constraints, $path)` to validate a nested value.

## Session, cookies and flash messages

They are configured in `config/framework/app.yaml`:

```yaml
name: '%env(APP_NAME)%'
secret: '%env(APP_SECRET)%'

session:
  name: NEOSESSID
  lifetime: 0
  gc_maxlifetime: 1440
  save_path: '%kernel.root_path%/var/sessions'

cookie:
  lifetime: 0
  path: /
  domain: ~
  secure: auto
  httponly: true
  samesite: Lax

flash:
  key: _flashes
```

| Option | Description |
|---|---|
| `secret` | key used to sign cookies (`APP_SECRET`, generated by `neo install`) |
| `session.name` | name of the session cookie |
| `session.lifetime` | lifetime of the session cookie in seconds (`0` = until the browser is closed) |
| `session.gc_maxlifetime` | seconds of inactivity after which the session data can be deleted |
| `session.save_path` | directory of the session files |
| `cookie.lifetime` | default lifetime of the cookies in seconds (`0` = until the browser is closed) |
| `cookie.path`, `cookie.domain` | default path and domain of the cookies (also used by the session cookie) |
| `cookie.secure` | `true`, `false` or `auto` (secure when the request is in HTTPS) |
| `cookie.httponly` | hides the cookies from JavaScript |
| `cookie.samesite` | `Lax`, `Strict` or `None` |
| `flash.key` | session key of the flash messages |

### Session

The session is the native PHP session, stored in `var/sessions/`. It is started only when it is used: reading a value when the browser has no session cookie does not start it, so anonymous visitors get no session cookie. It is saved at the end of the request.

```php
public function login(Request $request): Response
{
    $session = $this->getSession();
    $session->regenerate();
    $session->set('user_id', 42);

    return $this->redirectToRoute('home');
}
```

`SessionInterface`: `get()`, `set()`, `has()`, `remove()`, `all()`, `clear()`, `regenerate()`, `invalidate()`, `getId()`, `getName()`, `start()`, `isStarted()`, `save()`.

### Cookies

Cookies set during the request are added to the response automatically.

```php
$cookies = $this->getCookies();

$cookies->set('theme', 'dark', ['lifetime' => 3600 * 24 * 30]);
$cookies->set('remember', 'user-42', ['signed' => true]);
$cookies->remove('theme');

$theme = $cookies->get('theme', 'light');
$user = $cookies->getSigned('remember');
```

| Option | Description |
|---|---|
| `lifetime`, `path`, `domain`, `secure`, `httponly`, `samesite` | override the values of `config/framework/app.yaml` |
| `signed` | signs the value with `APP_SECRET` (HMAC SHA-256) |

`getSigned()` returns the value only when its signature is valid: a cookie modified by the client returns the default value. `get()` returns the raw value.

### Flash messages

A flash message is stored in the session until it is read.

```php
$this->addFlash('success', 'Your profile has been saved.');
```

```twig
{% for message in flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}

{% for type, messages in flashes() %}
    {% for message in messages %}
        <div class="alert alert-{{ type }}">{{ message }}</div>
    {% endfor %}
{% endfor %}
```

```php
<?php foreach ($this->flashes('success') as $message): ?>
    <div class="alert alert-success"><?= $this->e($message) ?></div>
<?php endforeach ?>
```

`FlashInterface`: `add()`, `get($type)` and `all()` (read and remove), `peek($type)` and `peekAll()` (read only), `has()`, `clear()`.

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
| `APP_SECRET` | none | key used to sign cookies |

### YAML configuration

Every `*.yaml` file of `config/` is loaded, except `routes.yaml`, `config/routes/` and `services.yaml`. The key is the file path:

| File | Key |
|---|---|
| `config/framework/app.yaml` | `framework.app` |
| `config/packages/mail.yaml` | `packages.mail` |

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
| `%kernel.cache_path%` | `var/cache/` directory |
| `%kernel.environment%` | `APP_ENV` |
| `%kernel.debug%` | debug mode (bool) |
| `%kernel.version%` | NeoPHP version |
| `%any.config.key%` | value of another configuration key |
| `%%` | a literal `%` |

Only `%env(...)%` and keys containing a dot (`%kernel.root_path%`, `%framework.app.name%`) are placeholders: `%datetime%` or `%type%` are kept as is.

A value made of a single placeholder keeps its type (`'%kernel.debug%'` is a bool). An undefined environment variable or configuration key throws a `ConfigException`.

## Logger

PSR-3 compatible logger (same methods and signatures), without dependency.

```php
use NeoPHP\Component\Logger\Contract\LoggerInterface;
use NeoPHP\Component\Logger\LoggerManager;

public function index(LoggerInterface $logger, LoggerManager $loggers): Response
{
    $logger->info('User {user} logged in', ['user' => 'neo']);
    $logger->error('Payment failed', ['exception' => $exception]);

    $loggers->channel('framework')->warning('Cache cleared');
}
```

Methods: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`, `log($level, ...)`. `{key}` placeholders in the message are replaced by the context values; the context is written as JSON.

`config/framework/logger.yaml`

```yaml
channels:
  app:
    enabled: true
    extension: log
  framework:
    enabled: true
    extension: log
    minimum_level: warning

rotation:
  enabled: true
  max_files: 30
  when:
    filesize: 10M
    every: day

archive:
  enabled: true
  extension: zip

settings:
  path: '%kernel.root_path%/var/log'
  format_message: '[%datetime%] %channel%.%type% %message% %context%'
  date_format: 'Y-m-d H:i:s'
  timezone: Europe/Paris
  minimum_level: debug
  default_channel: app
```

| Option | Description |
|---|---|
| `channels.<name>.enabled` | writes the channel into `<path>/<name>.<extension>` |
| `channels.<name>.extension` | file extension (`log`, `txt`...) |
| `channels.<name>.minimum_level` | overrides `settings.minimum_level` for the channel |
| `channels.<name>.path` | overrides `settings.path` for the channel |
| `rotation.enabled` | enables the rotation |
| `rotation.when.filesize` | rotates when the file exceeds a size (`500K`, `10M`, `1G`, bytes, `~` = never) |
| `rotation.when.every` | rotates every `minute`, `hour`, `day`, `week`, `month`, `year` (`~` = never) |
| `rotation.max_files` | keeps only the N most recent rotated files |
| `archive.enabled` / `archive.extension` | compresses rotated files as `zip` (PHP `zip` extension) or `gz` |
| `settings.format_message` | `%datetime%`, `%channel%`, `%type%` (or `%level%`), `%message%`, `%context%` |
| `settings.date_format` | PHP date format of `%datetime%` |
| `settings.timezone` | timezone of the dates (`~` = PHP `date.timezone`) |
| `settings.minimum_level` | lowest level written: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency` |
| `settings.default_channel` | channel used by `LoggerInterface` (default: first channel) |

Rotated files are named after the period (`app-2026-09-23.log`) or the rotation time (`app-2026-09-24_14-05-12.log`). Uncaught errors (HTTP 500) are written in the `framework` channel when it exists.

## Architecture

```
src/
├── components/
│   ├── Asset          asset compilation, hashed builds, manifest
│   ├── Config         YAML configuration and placeholders
│   ├── Container      dependency injection container, autowiring, #[Autowire], #[Inject], providers
│   ├── Controller     controller resolution and AbstractController (made of traits)
│   ├── Cookie         cookies read from the request, queued, signed
│   ├── Event          event dispatcher, listeners, subscribers
│   ├── Exception      FrameworkException and error pages
│   ├── Flash          flash messages stored in the session
│   ├── Http           Request, Response, JsonResponse, RedirectResponse
│   ├── Kernel         boot, request lifecycle, kernel events, class discovery, cache, cache:clear│   ├── Logger         PSR-3 logger, channels, rotation, archives
│   ├── Middleware     middlewares, pipeline, aliases and groups
│   ├── Routing        YAML and attribute routes, cache, matching, URL generation
│   ├── Service        config/services.yaml, resources, aliases, interfaces
│   ├── Validator      constraints (attributes or objects), violations, groups
│   ├── Session        native PHP session, started on demand
│   └── View           PHP and Twig templates, view helpers discovery
├── packages/
│   ├── Dotenv         .env files loader
│   └── Yaml           YAML parser
└── process/
    ├── Console        neo command line and commands discovery
    └── Installer      project skeleton generation
```

Each feature follows the same layout:

```
Feature/FeatureManager.php
Feature/Provider/FeatureProvider.php
Feature/Contract/FeatureInterface.php
Feature/Contract/AbstractFeature.php
Feature/Helper/Controller/FeatureController.php (optional)
Feature/Helper/View/FeatureViewHelper.php       (optional)
Feature/Helper/Console/FeatureXxxCommand.php    (optional)
Feature/Helper/Listener/FeatureListener.php     (optional)
```

## Changes

- Initial version: routes in YAML, YAML parser, PHP views, controllers, container and centralized exceptions.
- HTTP layer: `Request`, `Response`, `JsonResponse`, `RedirectResponse`, HTTP exceptions, JSON errors.
- Console `neo` with the `install`, `serve` and `route:list` commands.
- `bin/neo` generated in the project by `neo install`.
- Configuration: `.env` files, `config/**/*.yaml`, placeholders `%kernel.*%`, `%env(...)%` and `%config.key%`.
- Logger: PSR-3 compatible logger, channels, rotation by size or period, zip/gz archives (v1.1.0).
- Views: optional Twig engine, engine-agnostic view helpers discovered in each feature `Helper/View/` directory, `config/framework/view.yaml`; the `asset()` helper is removed (v1.2.0).
- Assets: `assets/` compiled into `public/builds/` with hashed names, `manifest.json`, `asset()` helper, `asset:reload [--minify]` command, commands discovered in `Helper/Console/` (v1.3.0).
- Controllers: `AbstractController` made of traits shipped by each feature in `Helper/Controller/` (v1.4.0).
- Routing: `#[Route]` attribute, controllers imported with `type: attribute` in `routes.yaml`, routes cache, `cache:clear` command, duplicate route names detected (v1.5.0).
- Session, cookies (signed with `APP_SECRET`) and flash messages configured in `app.yaml`, with controller traits and view helpers (v1.6.0).
- Middlewares: PSR-15 style interfaces, global middlewares (`middleware.yaml`, `#[AsMiddleware]`), aliases and groups, route middlewares (`#[Middleware]`, `#[Route(middlewares)]`, `routes.yaml`), `middleware:list` command (v1.7.0).
- Services: `#[Autowire]` on parameters, `#[Inject]` on properties, `config/services.yaml` (resources, arguments, calls, factories, aliases), shared services by default, interfaces bound to their single implementation, `service:list` command (v1.8.0).
- Events: PSR-14 style dispatcher, `#[AsListener]`, subscribers, `event.yaml`, stoppable events, kernel events (`RequestEvent`, `ControllerEvent`, `ResponseEvent`, `ExceptionEvent`, `TerminateEvent`) replacing `TerminableInterface`, `dispatch()` in controllers, `event:list` command (v1.9.0).
- Routing: attribute routes discovered with the kernel `ClassFinder` and routes cache stored with `ResourceCache`; an old routes cache is rebuilt automatically (v1.9.1).
- Validator: constraints usable as attributes or objects, validation of objects, values and arrays, groups, `Valid`, `All`, `Collection`, `Callback`, custom constraints with autowired validators, `validate()` in controllers, 422 JSON response for `ValidationFailedException` (v1.10.0).