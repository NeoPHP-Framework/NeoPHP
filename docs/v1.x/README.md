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
- events and listeners (PSR-14 style), with the kernel events
- a validator with constraints usable as attributes (`#[Assert\NotBlank]`) or objects (`new NotBlank()`)
- a database layer on top of PDO (MySQL / MariaDB, PostgreSQL, SQLite), configured in `config/framework/database.yaml`
- an ORM (data mapper): entities mapped with attributes, repositories, unit of work, lazy relations, query builders, migrations generated from the entities
- forms (`src/Form/XxxForm.php`) mapped to an entity or to an array, validated, rendered with themes (HTML or Bootstrap 5), with CSRF protection
- security: firewalls, login form, HTTP Basic, access tokens, remember-me, user providers, password hashers, roles, voters and `#[IsGranted]`, configured in `config/packages/security.yaml`
- `dump()` and `dd()` with a collapsible HTML dump (HTTP, templates, error page) and a colored console dump, disabled in production
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

```bash
php bin/neo                      # lists the commands, grouped by namespace
php bin/neo list make            # lists the commands of a namespace (also: php bin/neo make)
php bin/neo make:entity --help   # help of a command (also: php bin/neo help make:entity)
php bin/neo m:ent Post           # abbreviations are resolved when they are not ambiguous
php bin/neo --version
```

A mistyped command shows the closest names (`Did you mean this?`). Exit codes: `0` success, `1` failure, `2` invalid input (unknown command, option or missing argument).

### Global options

Every command accepts:

| Option | Description |
|---|---|
| `-h, --help` | displays the help of the command: description, usage, arguments, options, help and examples |
| `-q, --quiet` | no output (errors are still displayed), implies `--no-interaction` |
| `-v`, `-vv`, `-vvv`, `--verbose[=1\|2\|3]` | verbose, very verbose and debug output |
| `-f, --force` | force the operation (overwrite the generated files, skip the confirmations) |
| `-n, --no-interaction` | never ask a question: the default answers are used |
| `-e, --env=ENV` | the environment (`APP_ENV`), read by `bin/neo` before the kernel boots |
| `--ansi`, `--no-ansi` | force or disable the colors (`NO_COLOR` is also supported) |

When a command fails, the message is displayed in an error block; `-v` adds the exception class and file, `-vvv` the stack trace.

### Commands

| Command | Description |
|---|---|
| `help [command]` | displays the help of a command |
| `list [namespace]` | lists the commands |
| `install` | generates the project files (`--force` overwrites them) |
| `serve [--host=127.0.0.1] [-p 8000]` | starts the PHP development server |
| `route:list [filter]` (`routes`) | lists the routes |
| `middleware:list` | lists the global middlewares, the aliases and the groups |
| `service:list [filter]` | lists the services, the aliases and the interfaces bound automatically |
| `event:list [filter]` | lists the events and their listeners in the order they are called |
| `cache:clear` (`cc`) | clears `var/cache/` (routes, Twig templates...) |
| `asset:reload [-m]` | compiles `assets/` into `public/builds/` and rebuilds the manifest (`--minify`) |
| `database:create [-c name] [--if-not-exists]` (`db:create`) | creates the database of a connection |
| `database:drop [-c name] [--if-exists]` (`db:drop`) | drops the database of a connection, after a confirmation (or `--force`) |
| `database:query "SQL" [-c name]` (`db:query`) | executes a SQL query and displays the result |
| `make:command Class [name]` | generates a console command in `src/Command/` |
| `make:entity Post [field:type ...]` | generates an entity and its repository |
| `make:repository Post` | generates the repository of an entity |
| `make:migration [--empty] [-d "..."]` | generates a migration from the differences between the entities and the database |
| `migration:migrate [--dry-run]` (`migrate`) | executes the pending migrations |
| `migration:rollback [-s 1] [--dry-run]` (`rollback`) | rolls back the last executed migrations |
| `migration:status` | lists the migrations and their status |
| `make:form Post [Entity]` | generates `src/Form/PostForm.php`, with the fields of the entity when given |
| `make:user [User] [-p email]` | generates a user entity and its repository |
| `make:auth [SecurityController] [--twig]` | generates a login controller and its template |
| `make:voter Post` | generates `src/Security/Voter/PostVoter.php` |
| `security:hash-password [password] [UserClass]` | hashes a password (asked without echo when omitted) |
| `debug:container [filter\|id] [-d] [-p]` | lists the services of the container or shows one of them |

The `make:*` commands never overwrite an existing file, unless `--force` is used.

### Writing a command

```bash
php bin/neo make:command SendReport                    # app:send-report
php bin/neo make:command Admin/CleanUsers admin:clean-users
```

A command is a class declared with `#[AsCommand]` that extends `AbstractConsole`. Arguments, options, help and examples are declared in `configure()`, the work is done in `do()`, which returns an exit code:

```php
namespace App\Command;

use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\IO\InputArgument;
use NeoPHP\Process\Console\IO\InputOption;

#[AsCommand(name: 'app:send-report', description: 'Sends the monthly report', aliases: ['report'])]
class SendReportCommand extends AbstractConsole
{
    public function __construct(protected ReportService $reports)
    {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('month', InputArgument::REQUIRED, 'The month (YYYY-MM)');
        $input->addArgument('emails', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The recipients');
        $input->addOption('format', null, InputOption::VALUE_REQUIRED, 'pdf or csv', 'pdf');
        $input->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not send anything');
        $this->setHelp('The report is sent to the administrators when no email is given.');
        $this->addExample('app:send-report 2026-09');
        $this->addExample('app:send-report 2026-09 alice@example.com bob@example.com --format=csv');
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $month = $input->getArgument('month');

        if (!$output->confirm('Send the report of ' . $month . '?')) {
            return self::SUCCESS;
        }

        foreach ($output->progressIterate($this->reports->recipients($input->getArgument('emails'))) as $email) {
            $this->reports->send($month, $email, $input->getOption('format'), $input->getOption('dry-run'));
        }

        $output->success('Report sent.');

        return self::SUCCESS;
    }
}
```

- `#[AsCommand(name, description, aliases, hidden, help)]`: the metadata is read without creating the command; the constructor is autowired when the command runs.
- Commands are discovered in `src/` (any class with `#[AsCommand]`) and in the `Helper/Console/` directory of each framework feature.
- Arguments: `InputArgument::REQUIRED`, `OPTIONAL`, `IS_ARRAY` (the last one, collects the remaining values). Options: `InputOption::VALUE_NONE` (flag), `VALUE_REQUIRED`, `VALUE_OPTIONAL`, `VALUE_IS_ARRAY` (`--tag=a --tag=b`), with an optional one-letter shortcut.
- Accepted syntaxes: `--name=value`, `--name value`, `-n value`, `-nvalue`, grouped flags `-abc`, `--` ends the options.
- Missing required arguments, unknown options and missing values are reported before `do()` runs; a command can report its own invalid input by throwing `InvalidInputException` (exit code 2, usage displayed).
- The global options cannot be redefined; read `--force` with `$input->getOption('force')`.

### Output

| Method | Description |
|---|---|
| `writeln($message, $verbosity)`, `write()`, `newLine()` | raw output, shown from the given verbosity (`OutputInterface::VERBOSITY_VERBOSE`...) |
| `title()`, `section()`, `text()`, `comment()`, `listing()` | layout |
| `table($headers, $rows)`, `definitionList($definitions)` | tables and key / value lists |
| `success()`, `error()`, `warning()`, `caution()`, `info()`, `note()` | message blocks |
| `ask($question, $default, $validator)` | asks a question; the validator throws an exception to ask again, or returns the value |
| `confirm($question, $default)` | yes / no question |
| `choice($question, $choices, $default)` | returns the value (list) or the key (associative array) |
| `secret($question, $validator)` | hidden answer |
| `progressStart($max)`, `progressAdvance()`, `progressFinish()`, `progressIterate($iterable)` | progress bar |
| `isQuiet()`, `isVerbose()`, `isVeryVerbose()`, `isDebug()`, `isInteractive()`, `isDecorated()` | state |

Without interaction (`-n`, `-q` or a closed input), the questions return their default answer. Messages accept the tags `<info>`, `<success>`, `<comment>`, `<warning>`, `<error>`, `<question>`, `<title>`, `<muted>`, `<bold>` and `<underline>`; `Formatter::escape()` escapes a text that must be displayed as is.

### Migrating from v1.14

The old API is removed:

| Before | After |
|---|---|
| `extends AbstractCommand` | `extends AbstractConsole` + `#[AsCommand(name: ..., description: ...)]` |
| `protected string $name`, `$description` | `#[AsCommand]` |
| `execute(Input $input, Output $output)` | `do(InputInterface $input, OutputInterface $output)` |
| `$input->getArgument(0)` | `$input->addArgument('name', ...)` in `configure()`, then `$input->getArgument('name')` |
| `$input->getOption('x', $default)` | `$input->addOption('x', null, InputOption::VALUE_REQUIRED, '', $default)`, then `$input->getOption('x')` |
| commands only in `Helper/Console/` | any class of `src/` declared with `#[AsCommand]` |

`bin/neo` now passes the environment to the kernel: replace `new Kernel()` with `new Kernel(Input::environment($argv))` (`use NeoPHP\Process\Console\IO\Input;`), or run `php bin/neo install --force` on a copy of your project to get the new file.

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
| `getUser()`, `isGranted()`, `denyAccessUnlessGranted()`, `loginUser()`, `logoutUser()` | see [Security](#security) |

`AbstractController` has no method of its own: it is made of traits, and each feature ships its trait in `Feature/Helper/Controller/`:

| Trait | Methods |
|---|---|
| `Container/Helper/Controller/ContainerController` | `setContainer()`, `get()`, `has()` |
| `Cookie/Helper/Controller/CookieController` | `getCookies()` |
| `Event/Helper/Controller/EventController` | `dispatch()` |
| `Flash/Helper/Controller/FlashController` | `addFlash()` |
| `Http/Helper/Controller/HttpController` | `json()`, `redirect()`, `createNotFoundException()`, `createAccessDeniedException()` |
| `Routing/Helper/Controller/RoutingController` | `generateUrl()`, `redirectToRoute()` |
| `Security/Helper/Controller/SecurityController` (package) | `getUser()`, `isGranted()`, `denyAccessUnlessGranted()`, `loginUser()`, `logoutUser()`, `getLastUsername()`, `getLastAuthenticationError()` |
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
| `Security/Helper/View/*ViewHelper.php` (package) | `app_user()`, `is_granted()`, `logout_path()`, `last_username()`, `last_authentication_error()` |
| `Debug/Helper/View/DumpViewHelper.php` (package) | `dump()` |

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
    - { listener: App\Listener\Audit, method: onRegistered, priority: 10 }

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
| `File` | `maxSize` (`500k`, `2M`, `1Gi` or bytes), `mimeTypes` (`['application/pdf', 'image/*']`), `extensions` (`['pdf']`); validates an `UploadedFile`, a `SplFileInfo` or a path |
| `Image` | the `File` options (`mimeTypes` defaults to `image/*`), `minWidth`, `maxWidth`, `minHeight`, `maxHeight` |

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

## Database

The Database component gives a connection to a database and runs SQL queries with PDO. It is not an ORM: entities, repositories, migrations and the query builder belong to the ORM (v1.11.0).

It requires the `pdo` extension and the driver of the database:

| Driver | Extension | URL schemes |
|---|---|---|
| MySQL / MariaDB | `pdo_mysql` | `mysql://`, `mariadb://` |
| PostgreSQL | `pdo_pgsql` | `postgresql://`, `postgres://`, `pgsql://` |
| SQLite | `pdo_sqlite` | `sqlite://` |

### Configuration

`config/framework/database.yaml`:

```yaml
default: default

connections:
  default:
    url: '%env(DATABASE_URL)%'
```

`.env`:

```dotenv
# DATABASE_URL="mysql://user:password@127.0.0.1:3306/app?charset=utf8mb4"
# DATABASE_URL="postgresql://user:password@127.0.0.1:5432/app?charset=utf8"
DATABASE_URL="sqlite:///%kernel.root_path%/var/data.db"
```

A connection is configured with a `url`, with separate parameters, or both: the parameters override the parts of the URL.

```yaml
default: default

connections:
  default:
    url: '%env(DATABASE_URL)%'
  analytics:
    driver: pgsql
    host: '%env(ANALYTICS_HOST)%'
    port: 5432
    dbname: analytics
    user: '%env(ANALYTICS_USER)%'
    password: '%env(ANALYTICS_PASSWORD)%'
    charset: utf8
  cache:
    driver: sqlite
    path: var/cache.db
    options:
      ATTR_TIMEOUT: 5
```

| Parameter | Description |
|---|---|
| `url` | `driver://user:password@host:port/dbname?charset=...`; special characters of the user and the password are URL-encoded (`@` is `%40`) |
| `driver` | `mysql` (or `mariadb`), `pgsql` (or `postgresql`), `sqlite` |
| `host`, `port`, `dbname`, `user`, `password` | server connection (MySQL and PostgreSQL) |
| `unix_socket` | socket used instead of `host` and `port` |
| `charset` | `utf8mb4` by default for MySQL, `utf8` for PostgreSQL |
| `collation` | MySQL collation used by `database:create` |
| `sslmode` | PostgreSQL SSL mode |
| `path` | SQLite file, relative to the project root or absolute; `sqlite:///:memory:` for an in-memory database |
| `foreign_keys` | SQLite: set to `false` to disable `PRAGMA foreign_keys = ON` |
| `options` | PDO attributes, by name (`ATTR_TIMEOUT`) or number |

With a URL, `sqlite:///var/data.db` is relative to the project root and `sqlite:///%kernel.root_path%/var/data.db` is absolute.

The connections are opened on the first query. PDO throws exceptions and fetches associative arrays by default.

### Usage

In a controller, `getConnection()` returns the default connection, `getConnection('analytics')` another one:

```php
#[Route('/posts/{id}', name: 'post_show')]
public function show(int $id): Response
{
    $post = $this->getConnection()->fetchAssociative('SELECT * FROM post WHERE id = :id', ['id' => $id]);

    if ($post === null) {
        throw $this->createNotFoundException('Post not found.');
    }

    return $this->render('post/show.php', ['post' => $post]);
}
```

In a service, inject `ConnectionInterface` (default connection), a named connection with `#[Autowire(service: 'database.connection.<name>')]`, or `DatabaseInterface` to use all the connections:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Database\Contract\ConnectionInterface;

class PostRepository
{
    public function __construct(
        protected ConnectionInterface $connection,
        #[Autowire(service: 'database.connection.analytics')] protected ConnectionInterface $analytics,
    ) {
    }

    public function findPublished(): array
    {
        return $this->connection->fetchAllAssociative('SELECT * FROM post WHERE published = ? ORDER BY id DESC', [true]);
    }
}
```

| Method | Returns |
|---|---|
| `executeQuery($sql, $params)` | a `Result` |
| `executeStatement($sql, $params)` | the number of affected rows |
| `fetchAssociative()` / `fetchNumeric()` / `fetchObject()` | the first row, or `null` |
| `fetchOne()` | the first column of the first row, or `null` |
| `fetchAllAssociative()` / `fetchAllNumeric()` / `fetchAllObjects()` | all the rows |
| `fetchFirstColumn()` | the first column of every row |
| `fetchAllKeyValue()` | `[first column => second column]` |
| `fetchAllAssociativeIndexed()` | the rows indexed by their first column |
| `iterateAssociative()` | a generator, row by row |
| `insert($table, $data)` | the number of inserted rows |
| `update($table, $data, $criteria)` | the number of updated rows |
| `delete($table, $criteria)` | the number of deleted rows |
| `lastInsertId(?$sequence)` | the last inserted id (PostgreSQL: the sequence name, `post_id_seq`) |
| `quote($value)` / `quoteIdentifier($name)` | a quoted value / identifier |
| `getPdo()` | the PDO instance |

```php
$connection->insert('post', ['title' => 'Hello', 'status' => Status::Published, 'created_at' => new DateTimeImmutable()]);
$id = $connection->lastInsertId();

$connection->update('post', ['title' => 'Hello world'], ['id' => $id]);
$connection->delete('post', ['status' => [Status::Draft, Status::Archived]]);
```

`update()` and `delete()` require criteria: a `null` criterion becomes `IS NULL`, an array becomes `IN (...)`.

### Parameters

Parameters are positional (`?`) or named (`:name`), not both in the same query. The values are bound with their type: `int`, `bool`, `null`, `float`, backed enums (their value), `DateTimeInterface` (`Y-m-d H:i:s`), `Stringable`.

An array is expanded, for `IN` clauses:

```php
$connection->fetchAllAssociative('SELECT * FROM post WHERE id IN (:ids)', ['ids' => [1, 2, 3]]);
$connection->fetchAllAssociative('SELECT * FROM post WHERE id IN (?) AND views > ?', [[1, 2, 3], 10]);
```

An empty array becomes `NULL`, so `IN (NULL)` matches nothing.

### Transactions

```php
$connection->transactional(function (ConnectionInterface $connection): void {
    $connection->insert('order', ['reference' => 'A-001']);
    $connection->insert('order_line', ['order_id' => $connection->lastInsertId(), 'quantity' => 2]);
});
```

`transactional()` commits and returns the value of the callback, or rolls back and rethrows the exception. `beginTransaction()`, `commit()` and `rollBack()` can be called directly. Nested transactions use savepoints.

### Errors

| Exception | Thrown when |
|---|---|
| `ConnectionException` | the connection fails, or the PDO extension of the driver is missing |
| `QueryException` | a query fails; `getSql()` and `getParams()` return the query and its parameters |
| `DatabaseException` | the configuration is invalid, a connection does not exist... (parent of the two others) |

## ORM

The ORM (`src/packages/Orm`) is a data mapper built on the Database component: entities are plain PHP classes mapped with attributes, the ORM (`OrmInterface`, the entity manager) tracks them and writes the changes on `flush()`. It works with MySQL / MariaDB, PostgreSQL and SQLite.

### Configuration

`config/packages/orm.yaml` (every key is optional, these are the defaults):

```yaml
connection: ~

entity:
  path: src/Entity
  namespace: App\Entity

repository:
  path: src/Repository
  namespace: App\Repository

migration:
  path: migrations
  namespace: Migrations
  table: neo_migrations

proxy:
  path: '%kernel.cache_path%/orm/proxies'

ignore_tables: []
```

| Key | Description |
|---|---|
| `connection` | database connection used by the ORM (`database.yaml`); the default connection when empty |
| `entity` | directory and namespace of the entities, used by `make:entity` and `make:migration` |
| `repository` | directory and namespace of the repositories, used by `make:repository` and to find the repository of an entity |
| `migration` | directory, namespace and table of the migrations |
| `proxy` | directory of the generated proxy classes (cleared by `cache:clear`) |
| `ignore_tables` | tables ignored by `make:migration` (never created nor dropped) |

### Entities

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PostStatus;
use App\Repository\PostRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\Collection\ArrayCollection;
use NeoPHP\Package\Orm\Contract\CollectionInterface;
use NeoPHP\Package\Orm\Mapping as ORM;

#[ORM\Entity(repository: PostRepository::class)]
#[ORM\Index(columns: ['status', 'publishedAt'])]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    private PostStatus $status = PostStatus::Draft;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(Category::class, inversedBy: 'posts', nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToMany(Tag::class, inversedBy: 'posts')]
    private CollectionInterface $tags;

    #[ORM\OneToMany(Comment::class, mappedBy: 'post', cascade: ['persist', 'remove'], orphanRemoval: true, orderBy: ['createdAt' => 'DESC'])]
    private CollectionInterface $comments;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }
}
```

The table is the class name in snake_case (`BlogPost` → `blog_post`) and each column is the property name in snake_case (`publishedAt` → `published_at`). `#[ORM\Entity(table: 'users')]` and `#[ORM\Column(name: '...')]` change them.

| Attribute | Options |
|---|---|
| `#[ORM\Entity]` | `table`, `repository` |
| `#[ORM\Id]` | the identifier (one per entity) |
| `#[ORM\GeneratedValue]` | `strategy: 'auto'` (auto increment, default), `'uuid'` (UUID v7 generated on `persist()`), `'none'` (set by the application) |
| `#[ORM\Column]` | `name`, `type`, `length` (255), `nullable` (false), `unique`, `default`, `precision` / `scale` (decimal), `enumType` |
| `#[ORM\Index]` | `columns` (properties or columns), `name`, `unique`; repeatable, on the class |

The type is deduced from the property type when it is not given:

| Type | PHP type | Deduced from |
|---|---|---|
| `string` | `string` | `string` |
| `text` | `string` | |
| `integer`, `smallint`, `bigint` | `int` | `int` |
| `float` | `float` | `float` |
| `decimal` | `string` (formatted with the scale) | |
| `boolean` | `bool` | `bool` |
| `datetime`, `date`, `time` | `DateTime` | `DateTime`, `DateTimeInterface` |
| `datetime_immutable`, `date_immutable`, `time_immutable` | `DateTimeImmutable` | `DateTimeImmutable` |
| `json` | `array` | `array` |
| `guid` | `string` | |
| backed enum | the enum | a `BackedEnum` (stored as its value) |

### Relations

| Attribute | Owning side | Options |
|---|---|---|
| `#[ORM\ManyToOne(Category::class)]` | always (column `category_id`) | `inversedBy`, `joinColumn`, `nullable` (true), `onDelete` (`CASCADE`, `SET NULL`), `cascade`, `fetch` (`lazy`, `eager`) |
| `#[ORM\OneToMany(Comment::class, mappedBy: 'post')]` | never: mapped by the `ManyToOne` of the target | `cascade`, `orphanRemoval`, `orderBy` |
| `#[ORM\OneToOne(Profile::class)]` | without `mappedBy` (unique column `profile_id`) | `mappedBy`, `inversedBy`, `joinColumn`, `nullable`, `onDelete`, `cascade`, `orphanRemoval` |
| `#[ORM\ManyToMany(Tag::class)]` | without `mappedBy` (join table `post_tag`) | `mappedBy`, `inversedBy`, `joinTable`, `joinColumn`, `inverseJoinColumn`, `cascade`, `orderBy` |

- Only the owning side is written to the database: update it (the `add...()` / `set...()` methods generated by `make:entity` do it).
- `cascade: ['persist']` persists the new related entities, `cascade: ['remove']` removes them with the entity, `'all'` does both. Without `persist` cascade, a new entity found through a relation throws an exception on `flush()`.
- `orphanRemoval: true` removes an entity removed from the collection (`OneToMany`) or replaced (`OneToOne`).
- Collections (`OneToMany`, `ManyToMany`) are typed `CollectionInterface`: an `ArrayCollection` for a new entity, a lazy `PersistentCollection` loaded on first use for an entity read from the database.
- `ManyToOne` and `OneToOne` relations are loaded lazily with a proxy (a generated subclass in `var/cache/orm/proxies`) that loads the entity on its first method call; `getId()` does not load it. A `final` class, or a class with `__get()`, is loaded immediately instead.

### Persisting

```php
$orm = $this->getOrm();

$post = (new Post())->setTitle('Hello')->setCategory($category);
$post->addTag($tag);

$orm->persist($post);
$orm->flush();

$post->setTitle('Hello world');
$orm->flush();

$orm->remove($post);
$orm->flush();
```

`flush()` computes the changes of every managed entity and writes them in one transaction: inserts (in the order of the relations), updates of the changed columns only, join tables, deletes.

| Method | Description |
|---|---|
| `persist($entity)` | manages a new entity (inserted on `flush()`) |
| `remove($entity)` | schedules the deletion |
| `flush()` | writes the changes |
| `find(Post::class, $id)` | the entity, or `null` |
| `getReference(Post::class, $id)` | a proxy, without query |
| `getRepository(Post::class)` | the repository of the entity |
| `createQueryBuilder()` / `createSqlQueryBuilder()` | the query builders |
| `refresh($entity)`, `detach($entity)`, `clear()`, `contains($entity)` | unit of work |
| `transactional(fn (OrmInterface $orm) => ...)` | runs the callback and flushes in a transaction |

The same entity is returned for the same row (identity map). In a controller, `getOrm()` and `getRepository(Post::class)` are available; elsewhere, inject `NeoPHP\Package\Orm\Contract\OrmInterface`.

### Repositories

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Enum\PostStatus;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class PostRepository extends AbstractRepository
{
    protected string $entityClass = Post::class;

    public function findLatestPublished(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->where('p.status = :status')->setParameter('status', PostStatus::Published)
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getResult();
    }
}
```

Repositories are services: inject them in controllers and services (`public function index(PostRepository $posts)`). An entity without repository gets a generic `EntityRepository`.

| Method | Returns |
|---|---|
| `find($id)` | an entity or `null` |
| `findAll($orderBy)` | all the entities |
| `findBy(['category' => $category, 'status' => [PostStatus::Draft, PostStatus::Published]], ['title' => 'ASC'], $limit, $offset)` | the matching entities; an array becomes `IN`, `null` becomes `IS NULL` |
| `findOneBy($criteria, $orderBy)` | the first matching entity or `null` |
| `count($criteria)` | the number of matching entities |
| `createQueryBuilder('p')` | a query builder selecting the entity |
| `save($entity, $flush = false)` / `delete($entity, $flush = false)` | `persist()` / `remove()`, then `flush()` if asked |

### Query builder

The entity query builder uses properties (`p.publishedAt`) and relations (`p.category`); it translates them to columns and joins:

```php
$posts = $orm->createQueryBuilder()
    ->select('p', 'c')
    ->from(Post::class, 'p')
    ->leftJoin('p.category', 'c')
    ->join('p.tags', 't')
    ->where('t.name IN (:tags)')
    ->andWhere('p.category = :category')
    ->setParameter('tags', ['php', 'orm'])
    ->setParameter('category', $category)
    ->orderBy('p.title')
    ->setMaxResults(20)
    ->getResult();
```

| Method | Description |
|---|---|
| `select()` / `addSelect()` | an alias selects entities (a joined alias loads the relation in the same query), anything else is a scalar expression (`COUNT(p.id) AS total`) |
| `from(Post::class, 'p')` | the root entity |
| `join()` / `innerJoin()` / `leftJoin()` | a relation (`'p.category'`), or an entity with a condition (`Category::class, 'c', 'c.id = p.category'`); an extra condition is added with `AND` |
| `where()` / `andWhere()` / `orWhere()`, `groupBy()`, `having()`, `orderBy()` / `addOrderBy()` | clauses; `p.category` is the foreign key column |
| `setParameter()` / `setParameters()` | named parameters; an entity becomes its id, an array is expanded for `IN` |
| `setMaxResults()` / `setFirstResult()` | limit and offset |
| `getResult()` | the entities (or rows `[entity, scalar...]` when scalars are selected too) |
| `getOneOrNullResult()` / `getSingleResult()` | one entity (`NonUniqueResultException`, `NoResultException`) |
| `getSingleScalarResult()`, `getScalarResult()`, `getArrayResult()` | a value, raw rows, entities as arrays |
| `getSQL()` | the generated SQL |

`createSqlQueryBuilder()` returns a SQL query builder working on tables and columns, usable without entities:

```php
$rows = $orm->createSqlQueryBuilder()
    ->select('c.name', 'COUNT(p.id) AS total')
    ->from('category', 'c')
    ->leftJoin('post', 'p', 'p.category_id = c.id')
    ->groupBy('c.name')
    ->fetchAllAssociative();

$orm->createSqlQueryBuilder()->update('post')->set('views', 'views + 1')->where('id = :id')->setParameter('id', 1)->executeStatement();
```

It also builds `insert()` / `values()`, `update()` / `set()` and `delete()` queries, and runs them with `executeQuery()`, `executeStatement()`, `fetchAllAssociative()`, `fetchAssociative()`, `fetchOne()` and `fetchFirstColumn()`.

### Lifecycle callbacks and events

```php
#[ORM\PrePersist]
public function onPrePersist(): void
{
    $this->createdAt = new DateTimeImmutable();
}

#[ORM\PreUpdate]
public function onPreUpdate(PreUpdateEvent $event): void
{
    if ($event->hasChangedField('title')) {
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

| Callback attribute | Event (`NeoPHP\Package\Orm\Event\`) | When |
|---|---|---|
| `#[ORM\PrePersist]` | `PrePersistEvent` | on `persist()` |
| `#[ORM\PostPersist]` | `PostPersistEvent` | after the insert |
| `#[ORM\PreUpdate]` | `PreUpdateEvent` (`getChangeSet()`, `hasChangedField()`, `getOldValue()`, `getNewValue()`) | before the update; the changes made in the callback are saved |
| `#[ORM\PostUpdate]` | `PostUpdateEvent` | after the update |
| `#[ORM\PreRemove]` | `PreRemoveEvent` | on `remove()` |
| `#[ORM\PostRemove]` | `PostRemoveEvent` | after the delete |
| `#[ORM\PostLoad]` | `PostLoadEvent` | after the entity is loaded |
| | `PreFlushEvent`, `PostFlushEvent` | around `flush()` |

The events are dispatched with the event dispatcher: a listener receives them like any other event (`#[AsListener]` on a method taking `PrePersistEvent`, or `LifecycleEvent` for all of them).

### Generating code

```bash
php bin/neo make:entity Category name:string:100 posts:OneToMany:Post:category
php bin/neo make:entity Post title:string:120 content:text? status:enum:App\\Enum\\PostStatus publishedAt:datetime_immutable? category:ManyToOne:Category tags:ManyToMany:Tag
php bin/neo make:repository Post
```

`make:entity` generates the entity (properties, getters, setters, `add...()` / `remove...()` for collections) and its repository. A field is `name:type`; a trailing `?` makes it nullable. Types: `string[:length]`, `text`, `integer`, `smallint`, `bigint`, `float`, `decimal[:precision[:scale]]`, `boolean`, `datetime`, `datetime_immutable`, `date`, `date_immutable`, `time`, `json`, `guid`, `enum:Class`, and the relations `ManyToOne:Target`, `OneToOne:Target`, `OneToMany:Target[:mappedBy]`, `ManyToMany:Target`. The inverse side of a relation is not generated in the target entity.

### Migrations

```bash
php bin/neo make:migration --description="Blog schema"
php bin/neo migration:migrate
php bin/neo migration:status
php bin/neo migration:rollback
```

`make:migration` compares the entities to the database (tables, columns, indexes, foreign keys) and writes `migrations/Migration_{hash}.php` with the SQL of the database in use. The hash starts with the creation time, so migrations run in the order they were generated.

```php
<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a0d70c0b0beeb3 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Blog schema';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `category` (...)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('DROP TABLE `category`');
    }
}
```

- `make:migration` refuses to run while migrations are pending, and does nothing when the database is in sync. `--empty` creates an empty migration to write by hand.
- The executed migrations are stored in the `neo_migrations` table. Each migration runs in a transaction on PostgreSQL and SQLite (MySQL commits DDL statements immediately).
- On SQLite, a changed table is rebuilt (new table, copy of the data, rename), with the foreign keys disabled during the migration.
- The generated SQL can be edited before `migration:migrate`; `$this->connection` is available for data migrations.

### Limits

Composite identifiers, inheritance mapping, readonly properties and changes of the primary key are not supported. The ORM should be cleared (`clear()`) after a failed `flush()`.

## Forms

A form is a class of `src/Form/` extending `NeoPHP\Component\Form\Contract\AbstractForm`. It works with an entity (or any object) or with an array.

```bash
php bin/neo make:form Post Post
php bin/neo make:form Contact
```

`make:form Post Post` reads the mapping of the entity `App\Entity\Post` and adds a field for each column and owning relation (text, textarea, number, checkbox, date, enum, entity...). Without entity, the form works with an array.

```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Enum\PostStatus;
use NeoPHP\Component\Form\Contract\AbstractForm;
use NeoPHP\Component\Form\FormBuilder;
use NeoPHP\Component\Form\Type\EnumType;
use NeoPHP\Component\Form\Type\TextareaType;
use NeoPHP\Component\Form\Type\TextType;
use NeoPHP\Package\Orm\Helper\Form\EntityType;

class PostForm extends AbstractForm
{
    protected ?string $entityClass = Post::class;

    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Title'])
            ->add('content', TextareaType::class, ['required' => false, 'help' => 'Markdown is allowed.'])
            ->add('status', EnumType::class, ['class' => PostStatus::class])
            ->add('category', EntityType::class, ['class' => Category::class, 'choice_label' => 'name'])
            ->add('tags', EntityType::class, ['class' => Tag::class, 'multiple' => true, 'required' => false]);
    }
}
```

`$entityClass` binds the form to an entity (it is the `data_class` option; `configureOptions()` can return `['data_class' => Post::class]` too). Without `$entityClass`, the form works with an array. `configureOptions()` returns the default options of the form (`method`, `csrf_protection`, `validation_groups`...).

### In a controller

```php
#[Route('/posts/new', name: 'post_new', methods: ['GET', 'POST'])]
#[Route('/posts/{id}/edit', name: 'post_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, PostRepository $posts, ?int $id = null): Response
{
    $post = $id === null ? new Post() : ($posts->find($id) ?? throw $this->createNotFoundException());
    $form = $this->createForm(PostForm::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $posts->save($post, true);
        $this->addFlash('success', 'Post saved.');

        return $this->redirectToRoute('post_edit', ['id' => $post->getId()]);
    }

    return $this->render('post/edit.html.twig', ['form' => $form]);
}
```

- `createForm(PostForm::class, $post, $options)` builds the form; `handleRequest($request)` submits it when the request has the method of the form (`POST` by default) and a value named like the form (`post`).
- The submitted values are converted (strings to `int`, `DateTimeImmutable`, enums, entities...) and written into the entity with its setters (`setTitle()`), its `add...()` / `remove...()` methods for collections, or its properties.
- `isValid()` checks the CSRF token, the `constraints` of the fields and the constraints of the entity (`#[Assert\NotBlank]`...). Each violation is attached to the field of its property.
- A value that cannot be converted (`abc` in an integer field, an unknown choice) gives the error `invalid_message` on the field. An empty field written into a setter that does not accept `null` gives `This value should not be blank.`.
- Without entity, `getData()` returns an array (`['name' => 'Bob', 'email' => ...]`); `createForm(ContactForm::class, ['name' => 'Bob'])` sets the initial values.
- `createFormBuilder($data)->add(...)->getForm()` builds a form without class.
- `getClickedButton()` returns the submit button used; `getErrors(true)` returns all the errors.

### Field types

| Type | Model value | Specific options |
|---|---|---|
| `TextType`, `TextareaType`, `EmailType`, `UrlType`, `TelType`, `SearchType`, `ColorType` | `string` or `null` | |
| `PasswordType` | `string` | `always_empty` (true: never rendered back) |
| `HiddenType` | `string` | |
| `IntegerType` | `int` | `input` (`number` or `string`) |
| `NumberType` | `float` | `scale`, `input` (`number` or `string` for decimals), `html5` |
| `CheckboxType` | `bool` | `value` |
| `ChoiceType` | the chosen value(s) | `choices` (`['Label' => value]`), `multiple`, `expanded` (radios / checkboxes), `placeholder`, `choice_label`, `choice_value`, `choice_attr` |
| `EnumType` | enum case(s) | `class`; the label is `label()` / `getLabel()` of the enum when it exists, the case name otherwise |
| `EntityType` (`NeoPHP\Package\Orm\Helper\Form\`) | entity or entities | `class`, `choice_label` (property or callable), `query_builder` (`fn (PostRepository $repository) => $repository->createQueryBuilder('p')->orderBy('p.title')`), `choices`, `multiple`, `expanded` |
| `DateType`, `DateTimeType`, `TimeType` | `DateTimeImmutable` | `input` (`datetime_immutable`, `datetime`, `string`, `timestamp`), `with_seconds`, `html5` |
| `FileType` | `UploadedFile` (or a list with `multiple`) | `multiple` |
| `CollectionType` | array | `entry_type`, `entry_options`, `allow_add`, `allow_delete`, `delete_empty`, `prototype`, `prototype_name` |
| `RepeatedType` | the value of both fields | `type`, `options`, `first_options`, `second_options`, `first_name`, `second_name`, `invalid_message` |
| `SubmitType`, `ButtonType` | none (not mapped) | `isClicked()` |

Options shared by every field: `label` (`false` hides it), `label_attr`, `attr`, `row_attr`, `help`, `help_attr`, `required` (HTML attribute, not a constraint), `disabled`, `mapped` (`false`: not read nor written in the data), `property_path`, `data` (forced initial value), `empty_data`, `constraints`, `invalid_message`, `trim`. Options of the root form: `data_class`, `method`, `action`, `csrf_protection`, `csrf_field_name`, `csrf_token_id`, `validation_groups`, `allow_extra_fields`, `theme`.

A form can be used as a field of another form (`->add('address', AddressForm::class)`): its data is read and written in the property `address`.

`FileType` fields are usually `'mapped' => false`: move the file in the controller (`$form->get('image')->getData()?->move(...)`) and store its name in the entity.

`CollectionType` renders the attribute `data-prototype` (the HTML of a new entry, with `__name__` in place of the index) when `allow_add` is enabled, to add entries in JavaScript.

### Custom types

A type extends `NeoPHP\Component\Form\Contract\AbstractType`; it is created with the container, so its constructor is autowired.

```php
class TagsInputType extends AbstractType
{
    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function configureOptions(): array
    {
        return ['separator' => ','];
    }

    public function transform(mixed $data, array $options): mixed
    {
        return implode($options['separator'], (array) $data);
    }

    public function reverseTransform(mixed $data, array $options): mixed
    {
        return array_values(array_filter(array_map('trim', explode($options['separator'], (string) $data))));
    }
}
```

| Method | Role |
|---|---|
| `getParent()` | parent type (`FormType` by default): its options, rendering and behaviour are inherited |
| `configureOptions()` | options of the type and their default values; an unknown option throws an exception |
| `buildForm()` | adds the fields |
| `buildView()` | adds variables to the view (`$view->vars`) |
| `transform()` / `reverseTransform()` | converts the model value to the view value and back; throw `TransformationFailedException` for an invalid value |
| `getBlockPrefix()` | name used by the themes (`tags_input_widget`); by default, the class name without `Type` / `Form` in snake_case |

### Rendering

PHP templates:

```php
<?= $this->form_start($form) ?>
<?= $this->form_errors($form) ?>
<?= $this->form_row($form['title']) ?>
<?= $this->form_row($form['content'], ['attr' => ['rows' => 8]]) ?>
<button type="submit">Save</button>
<?= $this->form_end($form) ?>
```

Twig templates:

```twig
{{ form_start(form) }}
    {{ form_errors(form) }}
    {{ form_row(form.title) }}
    {{ form_label(form.tags, 'Tags') }}
    {{ form_widget(form.tags, {attr: {class: 'tags'}}) }}
    <button type="submit">Save</button>
{{ form_end(form) }}
```

| Helper | Renders |
|---|---|
| `form(form)` | the whole form |
| `form_start(form, vars)` | `<form>` (method, action, `enctype` when there is a file field; `_method` hidden field for `PUT`, `PATCH`, `DELETE`) |
| `form_end(form, vars)` | the fields not rendered yet (CSRF token included) and `</form>`; `{render_rest: false}` skips them |
| `form_row(field, vars)` | label, widget, help and errors |
| `form_label(field, label, vars)`, `form_widget(field, vars)`, `form_errors(field)`, `form_help(field)` | one part of a field |
| `form_rest(form)` | the fields not rendered yet |

The helpers accept the form or `$form->createView()`. The variables (`attr`, `label`, `label_attr`, `row_attr`, `help`...) override the options of the field.

### Themes

`config/framework/form.yaml`:

```yaml
theme: default
csrf_protection: true
csrf_field_name: _token
```

| Theme | Markup |
|---|---|
| `default` | plain HTML (`<div>`, `<label>`, `<ul class="form-errors">`) |
| `bootstrap5` | Bootstrap 5 (`mb-3`, `form-label`, `form-control`, `form-select`, `form-check`, `is-invalid`, `invalid-feedback`) |
| a class | a class extending `NeoPHP\Component\Form\Theme\DefaultTheme` (or `Bootstrap5Theme`) |

The theme of one form is set with the option `'theme' => 'bootstrap5'`. A theme renders blocks named after the types: for an `EmailType` field, the widget is rendered by `emailWidget()`, else `textWidget()`, else `formWidget()`. A custom theme overrides the methods it needs (`formRow()`, `choiceWidget()`, `checkboxRow()`...), or adds blocks for a type (`tagsInputWidget()`) or for one form (`postRow()` for the form `post`).

## CSRF

The CSRF component (`src/components/Csrf`) creates tokens stored in the session. Each token is masked differently every time it is rendered.

- **Forms**: a hidden field `_token` is added to every form and checked by `isValid()` (error `The CSRF token is invalid. Please try to resubmit the form.`). Disable it with `'csrf_protection' => false` (API forms). The token id is the name of the form, or `csrf_token_id`.
- **Hand-written HTML**: `csrf_token('delete-post-' ~ post.id)` / `$this->csrf_token('delete-post-' . $post->getId())` returns a token, `csrf_field('contact')` returns the hidden input. In a controller, `isCsrfTokenValid('delete-post-' . $id, $request->request->get('_token'))` checks it and `getCsrfToken('id')` returns one. Outside controllers, inject `NeoPHP\Component\Csrf\Contract\CsrfInterface`.
- **Attribute**: `#[Csrf('delete-post-{id}')]` on a controller or a method checks the token of the `POST`, `PUT`, `PATCH` and `DELETE` requests before the controller (route middleware), and throws an `InvalidCsrfTokenException` (HTTP 403) when it is missing or invalid. `{id}` is replaced by the route parameter. The token is read from the header `X-CSRF-TOKEN` (for AJAX requests), then from the field `_token`.

```php
#[Route('/posts/{id}/delete', name: 'post_delete', methods: ['POST'])]
#[Csrf('delete-post-{id}')]
public function delete(Post $post): Response
```

| `#[Csrf]` option | Default |
|---|---|
| `id` | required; `{parameter}` placeholders are replaced by the route parameters |
| `field` | `_token` |
| `header` | `X-CSRF-TOKEN` |
| `methods` | `['POST', 'PUT', 'PATCH', 'DELETE']` |

`config/framework/csrf.yaml` (optional):

```yaml
field_name: _token
header_name: X-CSRF-TOKEN
session_key: _csrf
```

## Security

The Security package (`src/packages/Security`) authenticates the users (login form, HTTP Basic, access tokens, custom authenticators, remember-me) and checks their permissions (roles, role hierarchy, voters, `#[IsGranted]`, `access_control`). It is enabled when `config/packages/security.yaml` defines at least one firewall or one `access_control` rule; without it the package does nothing.

### Quick start

```bash
php bin/neo make:user                  # src/Entity/User.php + src/Repository/UserRepository.php
php bin/neo make:migration && php bin/neo migration:migrate
php bin/neo make:auth                  # src/Controller/SecurityController.php + templates/security/login.php (--twig for Twig)
php bin/neo security:hash-password secret
```

```yaml
# config/packages/security.yaml
providers:
  users:
    entity:
      class: App\Entity\User
      property: email

password_hashers:
  default: auto

firewalls:
  assets:
    pattern: ^/builds/
    security: false
  main:
    pattern: ^/
    provider: users
    form_login:
      login_path: app_login
      enable_csrf: true
      default_target_path: /
    logout:
      path: app_logout
      target: /
    remember_me:
      lifetime: 604800
    login_throttling:
      max_attempts: 5
      interval: 60

role_hierarchy:
  ROLE_ADMIN: [ROLE_USER]

access_control:
  - { path: ^/admin, roles: ROLE_ADMIN }
  - { path: ^/profile, roles: IS_AUTHENTICATED }
```

`neo install` creates a default `config/packages/security.yaml` (memory provider without users, login form on `/login`).

### Users

A user implements `NeoPHP\Package\Security\Contract\UserInterface` (`getUserIdentifier()`, `getRoles()`) and `PasswordAuthenticatedUserInterface` (`getPassword()`) when it logs in with a password. `make:user [User] [--property=email] [--force]` generates an entity with `id`, the identifier property (unique), `roles` (JSON, `ROLE_USER` always added) and `password`.

| Provider | Configuration |
|---|---|
| ORM entity | `entity: { class: App\Entity\User, property: email }`; without `property`, the repository must define `loadUserByIdentifier()` |
| Memory | `memory: { users: { admin: { password: '$2y$...', roles: [ROLE_ADMIN] } } }` (`InMemoryUser`) |
| Chain | `chain: { providers: [users, admins] }` |
| Custom | `id: App\Security\MyProvider` (implements `UserProviderInterface`) |

A firewall uses its `provider` option, or the single provider when only one is defined. The user is stored in the session by identifier and reloaded on the first access to the user of the request; when its password changed, the session is closed.

### Passwords

`password_hashers` maps a class (or an interface, or `default`) to `auto`, `bcrypt`, `argon2i`, `argon2id`, `plaintext`, `{ algorithm: bcrypt, cost: 12 }` or `{ id: App\Security\MyHasher }` (implements `PasswordHasherInterface`). Inject `NeoPHP\Package\Security\Hasher\UserPasswordHasher`:

```php
$user->setPassword($hasher->hashPassword($user, $plainPassword));
$hasher->isPasswordValid($user, $plainPassword);
```

When a password hashed with older options is valid, it is rehashed and saved (entity provider).

### Firewalls

The first firewall whose `pattern` (regular expression, plus optional `host`, `methods`, `ips`) matches the request is used.

| Option | Description |
|---|---|
| `security: false` | no authentication at all (assets...) |
| `stateless: true` | nothing stored in the session (APIs) |
| `provider` | name of the user provider |
| `context` | firewalls with the same context share the logged user |
| `form_login` | login form (see below) |
| `http_basic` | `{ realm: 'Secured Area' }` |
| `access_token` | `{ token_handler: App\Security\ApiTokenHandler, header: Authorization, token_type: Bearer, query_parameter: ~, realm: ~ }` |
| `custom_authenticators` | list of classes implementing `AuthenticatorInterface` |
| `remember_me` | `{ lifetime: 604800, name: REMEMBERME, parameter: _remember_me, always: false, path: /, domain: ~, secure: auto, samesite: Lax }`: `parameter` is the checkbox of the login form, `always` sets the cookie on every login |
| `login_throttling` | `{ max_attempts: 5, interval: 60 }`: login attempts per IP + identifier (and 5 × more per IP) during `interval` seconds, stored in `var/cache/security/throttling/`; the IP is `REMOTE_ADDR` |
| `logout` | disabled unless declared: `{ path: /logout, target: /, invalidate_session: true, enable_csrf: false, csrf_parameter: _csrf_token, csrf_token_id: logout, clear_cookies: [] }`; the request on `path` is handled by the firewall |
| `user_checker` | class implementing `UserCheckerInterface` (`checkPreAuth()`, `checkPostAuth()`): banned or disabled accounts |
| `entry_point` | `form_login`, `http_basic`, `access_token` or a class implementing `EntryPointInterface` |

Paths accept a path (`/login`) or a route name (`app_login`).

When an anonymous user is denied, the entry point of the firewall answers: the login form redirects to `login_path` (the requested URL is restored after the login) or returns a 401 JSON response for AJAX/JSON requests, HTTP Basic returns a 401 with `WWW-Authenticate`, the access token returns a 401 JSON response. A logged user who is denied gets a 403 (except a remembered user on `IS_AUTHENTICATED_FULLY`, who is sent to the login form).

### Login form

| `form_login` option | Default |
|---|---|
| `login_path` | `/login` |
| `check_path` | `login_path` (the `POST` on this path is the login) |
| `username_parameter` / `password_parameter` | `_username` / `_password` |
| `enable_csrf` / `csrf_parameter` / `csrf_token_id` | `false` / `_csrf_token` / `authenticate` |
| `default_target_path` | `/` |
| `always_use_default_target_path` | `false` |
| `target_path_parameter` | `_target_path` (relative paths only) |
| `use_referer` | `false` |
| `failure_path` | `login_path` |

```php
#[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
public function login(): Response
{
    return $this->render('security/login.php', [
        'last_username' => $this->getLastUsername(),
        'error' => $this->getLastAuthenticationError(),
    ]);
}
```

The error is a safe message (`Invalid credentials.`, `Invalid CSRF token.`, `Too many failed login attempts, please try again in 1 minute(s).`): an unknown user and a wrong password give the same message.

### Access tokens and custom authenticators

```php
class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private ApiTokenRepository $tokens)
    {
    }

    public function getUserFrom(string $accessToken): string|UserInterface
    {
        return $this->tokens->findOneBy(['value' => $accessToken])?->getOwner()?->getEmail()
            ?? throw new BadCredentialsException('Invalid access token.');
    }
}
```

A custom authenticator extends `AbstractAuthenticator`:

```php
class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $users)
    {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $key = $request->headers->get('X-API-KEY');

        return Passport::selfValidating($key, fn (string $key): ?UserInterface => $this->users->findOneBy(['apiKey' => $key]));
    }
}
```

`new Passport($identifier, $password)` checks the password with the hasher, `Passport::selfValidating($identifier, $loader)` does not. `->csrf($id, $token)`, `->rememberMe()` and `->addCheck(fn (UserInterface $user) => ...)` add checks. `onAuthenticationSuccess()` / `onAuthenticationFailure()` return a `Response` or `null` (the request continues); throw a `CustomUserMessageAuthenticationException` to show your own message.

### Authorization

| Attribute | Granted when |
|---|---|
| `ROLE_*` | the user has the role, directly or through `role_hierarchy` |
| `IS_AUTHENTICATED` | a user is logged in (also `IS_AUTHENTICATED_REMEMBERED`) |
| `IS_AUTHENTICATED_FULLY` | logged in during this session (not by the remember-me cookie) |
| `IS_REMEMBERED` | logged in by the remember-me cookie |
| `PUBLIC_ACCESS` | always |
| anything else | decided by the voters |

```php
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin/posts/{id}')]
    #[IsGranted('ROLE_EDITOR')]
    #[IsGranted('ROLE_SUPER_ADMIN', statusCode: 404, message: 'Not found')]
    public function show(int $id): Response
    {
        $post = $this->posts->find($id) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted('POST_EDIT', $post);

        return $this->render('admin/post.php', ['post' => $post]);
    }

    #[Route('/admin/sections/{section}')]
    #[IsGranted('SECTION_ACCESS', subject: 'section')]
    public function section(string $section): Response
    {
        return $this->render('admin/section.php', ['section' => $section]);
    }
}
```

`#[IsGranted]` (class or method, repeatable) runs before the controller. `subject` is the name of a route parameter (or a list of names): the voter receives its raw value (`'news'`, `'42'`), so load entities in the controller and use `denyAccessUnlessGranted()` to vote on them. `statusCode` replaces the 403 by another status (404 hides the page) and never redirects to the login form. `access_control` rules are checked on every request, the first matching rule applies (`path`, `host`, `methods`, `ips`, `roles`); the user needs one of the `roles`.

A voter is a class of `src/` with `#[AsVoter(priority: 0)]` extending `AbstractVoter` (`make:voter Post` generates `src/Security/Voter/PostVoter.php` with `POST_VIEW`, `POST_EDIT` and `POST_DELETE`):

```php
#[AsVoter]
class PostVoter extends AbstractVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'POST_EDIT' && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $subject->getAuthor() === $token->getUser();
    }
}
```

`access_decision_manager: { strategy: affirmative, allow_if_all_abstain: false, allow_if_equal_granted_denied: true }`: `affirmative` (one voter grants), `consensus` (more grants than denials), `unanimous` (no voter denies), `priority` (the first voter that does not abstain). Voters can also be listed in `voters: [App\Security\MyVoter]`.

### Helpers

| Controller | View | |
|---|---|---|
| `getUser()` | `app_user()` | the logged user or `null` |
| `isGranted($attribute, $subject)` | `is_granted($attribute, $subject)` | `bool` |
| `denyAccessUnlessGranted($attribute, $subject, $message)` | | throws an `AccessDeniedException` (403 or entry point) |
| `getLastUsername()` | `last_username()` | last submitted username |
| `getLastAuthenticationError()` | `last_authentication_error()` | last login error message |
| `loginUser($user, $firewall, $rememberMe)` | | logs a user in (after a registration...) |
| `logoutUser()` | `logout_path()` | logs out and returns the redirection / URL of the logout of the firewall (with the CSRF token when enabled, `null` without `logout`) |

Outside controllers, inject `NeoPHP\Package\Security\Contract\SecurityInterface`.

### Events

| Event | When |
|---|---|
| `LoginSuccessEvent` | after a successful authentication (`getUser()`, `getToken()`, `getFirewall()`, `getAuthenticator()`, `setResponse()`) |
| `LoginFailureEvent` | after a failed authentication (`getException()`, `setResponse()`) |
| `LogoutEvent` | on logout (`getToken()`, `setResponse()`) |

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

## Debug

The Debug package (`src/packages/Debug`) dumps variables while developing. It is enabled when `APP_DEBUG` is true (`config/packages/debug.yaml`, `enabled: '%kernel.debug%'`); when it is disabled, `dump()` does nothing and `dd()` only stops the script (HTTP 500), so a forgotten dump never leaks data in production.

```php
dump($user);                 // dumps and continues, returns $user
dump($request, $post, 42);   // several values at once
dd($form->getData());        // dumps and stops the script
```

`dump()` and `dd()` are global functions loaded by Composer (`autoload.files`, run `composer update` or `composer dump-autoload` after upgrading), available everywhere: controllers, services, templates, commands and plain scripts. Each dump shows the file and line where it was called.

| Where | Output |
|---|---|
| HTTP | the dumps are collected and inserted at the top of the `<body>` of the response (before the text for non-HTML responses), so the session, the cookies and the headers still work after a `dump()`; `dd()` prints them immediately |
| Console | colored text on the standard output (plain text when it is not a terminal or when `NO_COLOR` is set) |
| Templates | `{{ dump(items, post) }}` / `<?= $this->dump($items) ?>` renders the dump at this place (nothing when disabled) |

The HTML dump is collapsible (click on ▼ / ▶): the first level is open, the deeper ones are closed. Values show their type and size (`array:3`, `"string"` with its length on hover), objects their class and id (`App\Entity\Post {#12}`), properties their visibility (`+` public, `#` protected, `-` private, `~` virtual), enums (`PostStatus::Draft "draft"`), closures (parameters, file, lines), dates, resources and binary strings (`b"\xFF"`). An object already dumped in the same value is shown as `{#12}` (no infinite recursion).

```yaml
# config/packages/debug.yaml
enabled: '%kernel.debug%'
max_depth: 10       # nested levels dumped
max_items: 250      # items per array / properties per object
max_string: 1000    # characters per string
expand_depth: 1     # levels open in the HTML dump
```

Outside `dump()`, inject `NeoPHP\Package\Debug\Contract\DebugInterface`: `toHtml($value)`, `toText($value, $label, $colors)`, `isEnabled()`.

When the debug mode is on, the error page uses the dumper to display the context of the exception (`FrameworkException` context) and the arguments of each frame of the stack trace (collapsed; PHP only records them when `zend.exception_ignore_args` is `Off`, the default of the development `php.ini`).

`php bin/neo debug:container [filter|id] [--dump] [--parameters]` lists the services of the container (id, kind: singleton / factory / instance / alias, class, resolved or not), `--parameters` lists the parameters (`kernel.*`), an id shows the details of a service (alias target, class, constructor arguments) and `--dump` builds it and dumps it.

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
| `DATABASE_URL` | `sqlite:///%kernel.root_path%/var/data.db` | URL of the default database connection |

`php bin/neo install` creates `.env`; when `.env` already exists, it adds the variables that are missing (with their comments) and keeps the others.

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
│   ├── Csrf           CSRF tokens, #[Csrf] attribute
│   ├── Database       PDO connections (MySQL, PostgreSQL, SQLite), queries, transactions
│   ├── Event          event dispatcher, listeners, subscribers
│   ├── Exception      FrameworkException and error pages
│   ├── Flash          flash messages stored in the session
│   ├── Form           forms, field types, data mapping, validation, themes, make:form
│   ├── Http           Request, Response, JsonResponse, RedirectResponse
│   ├── Kernel         boot, request lifecycle, kernel events, class discovery, cache, cache:clear
│   ├── Logger         PSR-3 logger, channels, rotation, archives
│   ├── Middleware     middlewares, pipeline, aliases and groups
│   ├── Routing        YAML and attribute routes, cache, matching, URL generation
│   ├── Service        config/services.yaml, resources, aliases, interfaces
│   ├── Session        native PHP session, started on demand
│   ├── Validator      constraints (attributes or objects), violations, groups
│   └── View           PHP and Twig templates, view helpers discovery
├── packages/
│   ├── Debug          dump() and dd(), HTML and console dumpers, debug:container
│   ├── Dotenv         .env files loader
│   ├── Orm            entities, unit of work, repositories, query builders, proxies, migrations
│   ├── Security       firewalls, authenticators, user providers, password hashers, voters, #[IsGranted]
│   └── Yaml           YAML parser
└── process/
    ├── Console        neo command line, #[AsCommand], input definitions, styled output, commands discovery
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
- Database: PDO connections configured in `database.yaml` with a URL or parameters (MySQL / MariaDB, PostgreSQL, SQLite), several connections, query and fetch methods, `insert()` / `update()` / `delete()`, array parameters expanded, nested transactions, `getConnection()` in controllers, `database:create`, `database:drop` and `database:query` commands; `neo install` adds the missing variables to an existing `.env` (v1.11.0).
- ORM package: entities mapped with attributes, `ManyToOne` / `OneToMany` / `OneToOne` / `ManyToMany` relations with lazy loading (generated proxies, lazy collections), cascade and orphan removal, unit of work with identity map and change tracking, repositories, entity and SQL query builders, lifecycle callbacks and events, enum / JSON / date / decimal types, `make:entity`, `make:repository`, `make:migration` (diff between the entities and the database), `migration:migrate`, `migration:rollback` and `migration:status` commands, `config/packages/orm.yaml` (v1.11.0).
- Forms and CSRF: form classes extending `AbstractForm` mapped to an entity or an array, field types (text, number, checkbox, choice, enum, entity, date, file, collection, repeated, submit...), conversion and data mapping, validation with the field and entity constraints, `default` and `bootstrap5` themes with `form_*` view helpers, `make:form` (fields generated from an entity); CSRF component with tokens in the session, automatic token in forms, `csrf_token()` / `csrf_field()` helpers, `isCsrfTokenValid()` in controllers and `#[Csrf]` attribute; `File` and `Image` constraints (v1.12.0).
- Security package: firewalls with login form, HTTP Basic, access tokens, custom authenticators and remember-me cookie, entity / memory / chain / custom user providers, password hashers with automatic rehash, login throttling, user checkers, logout with optional CSRF token, roles with hierarchy, voters (`#[AsVoter]`), `#[IsGranted]`, `access_control`, access decision strategies, `getUser()` / `isGranted()` / `denyAccessUnlessGranted()` / `loginUser()` / `logoutUser()` in controllers, `app_user()` / `is_granted()` / `logout_path()` view helpers, login events, `make:user`, `make:auth`, `make:voter` and `security:hash-password` commands, `config/packages/security.yaml` (v1.13.0).
- Debug package: `dump()` and `dd()` global functions, collapsible HTML dumps inserted in the response, colored console dumps, `dump()` view helper, dumps disabled when `APP_DEBUG` is false, exception context and stack trace arguments dumped on the error page, `debug:container` command, `config/packages/debug.yaml`; the container exposes `getDefinitions()` and `getAliases()` (v1.14.0).
- Console: commands declared with `#[AsCommand]` and extending `AbstractConsole` (`configure()` / `do()`), arguments and options definitions with validation, global options (`--help`, `-q`, `-v`/`-vv`/`-vvv`, `--force`, `-n`, `--env`, `--ansi`/`--no-ansi`), the same help layout for every command with examples, styled output (titles, tables, message blocks), questions (`ask`, `confirm`, `choice`, `secret`), progress bar, commands grouped by namespace, abbreviations and "Did you mean" suggestions, command aliases, `make:command`, commands discovered anywhere in `src/`, `--env` read by `bin/neo`; `AbstractCommand` is removed and every command is rewritten (v1.15.0).