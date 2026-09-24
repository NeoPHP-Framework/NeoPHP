<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Contract;

use ErrorException;
use NeoPHP\Component\Container\ContainerManager;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Container\Contract\ProviderInterface;
use NeoPHP\Component\Container\Provider\ContainerProvider;
use NeoPHP\Component\Controller\Contract\ControllerResolverInterface;
use NeoPHP\Component\Controller\Provider\ControllerProvider;
use NeoPHP\Component\Exception\ExceptionManager;
use NeoPHP\Component\Exception\Provider\ExceptionProvider;
use NeoPHP\Component\Http\Provider\HttpProvider;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Kernel\Exception\KernelException;
use NeoPHP\Component\Kernel\Provider\KernelProvider;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\Routing\Provider\RoutingProvider;
use NeoPHP\Component\View\Provider\ViewProvider;
use NeoPHP\Package\Yaml\Provider\YamlProvider;
use NeoPHP\Process\Console\Provider\ConsoleProvider;
use NeoPHP\Process\Installer\Provider\InstallerProvider;
use ReflectionObject;
use Throwable;

abstract class AbstractKernel implements KernelInterface
{
    public const VERSION = '1.0.0-dev';

    protected string $projectDir;

    protected string $environment;

    protected bool $debug;

    protected ?ContainerInterface $container = null;

    protected bool $booted = false;

    public function __construct(?string $environment = null, ?bool $debug = null, ?string $projectDir = null)
    {
        $this->projectDir = rtrim($projectDir ?? $this->detectProjectDir(), '/\\');
        $this->environment = $environment ?? (string) ($this->env('APP_ENV') ?? 'dev');

        $envDebug = $this->env('APP_DEBUG');
        $this->debug = $debug ?? ($envDebug !== null ? filter_var($envDebug, FILTER_VALIDATE_BOOLEAN) : $this->environment !== 'prod');
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerErrorHandler();

        $container = $this->createContainer();
        $container->instance(KernelInterface::class, $this);
        $container->instance(static::class, $this);

        foreach ($this->getParameters() as $name => $value) {
            $container->instance($name, $value);
        }

        $providers = [];

        foreach ([...$this->coreProviders(), ...$this->providers()] as $provider) {
            $provider = is_string($provider) ? new $provider() : $provider;

            if (!$provider instanceof ProviderInterface) {
                throw new KernelException('"{provider}" must implement {interface}.', 0, null, [
                    'provider' => get_debug_type($provider),
                    'interface' => ProviderInterface::class,
                ]);
            }

            $provider->register($container);
            $providers[] = $provider;
        }

        $this->container = $container;

        foreach ($providers as $provider) {
            $provider->boot($container);
        }

        $this->booted = true;
    }

    public function handle(Request $request): Response
    {
        try {
            $this->boot();

            $match = $this->getContainer()->get(RoutingInterface::class)->match($request->getMethod(), $request->getPath());

            $request->attributes->add($match->parameters);
            $request->attributes->set('_route', $match->getName());
            $request->attributes->set('_controller', $match->getController());

            $response = $this->getContainer()->get(ControllerResolverInterface::class)->dispatch($match->getController(), $request, $match->parameters);
        } catch (Throwable $exception) {
            $response = $this->handleException($exception, $request);
        }

        return $response->prepare($request);
    }

    public function run(): void
    {
        $this->handle(Request::fromGlobals())->send();
    }

    public function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            throw new KernelException('The kernel is not booted: call boot() first.');
        }

        return $this->container;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getConfigDir(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'config';
    }

    public function getTemplatesDir(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'templates';
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getParameters(): array
    {
        return [
            'kernel.project_dir' => $this->projectDir,
            'kernel.config_dir' => $this->getConfigDir(),
            'kernel.templates_dir' => $this->getTemplatesDir(),
            'kernel.environment' => $this->environment,
            'kernel.debug' => $this->debug,
            'kernel.version' => static::VERSION,
        ];
    }

    protected function handleException(Throwable $exception, Request $request): Response
    {
        $manager = $this->container !== null && $this->container->has(ExceptionManager::class)
            ? $this->container->get(ExceptionManager::class)
            : new ExceptionManager($this->debug);

        $status = $manager->getStatusCode($exception);
        $headers = $manager->getHeaders($exception);

        if ($request->wantsJson() || $request->isJson()) {
            return new JsonResponse($manager->renderJson($exception), $status, $headers);
        }

        return new Response($manager->render($exception), $status, $headers);
    }

    protected function providers(): iterable
    {
        return [];
    }

    protected function coreProviders(): array
    {
        return [
            ContainerProvider::class,
            KernelProvider::class,
            ExceptionProvider::class,
            YamlProvider::class,
            HttpProvider::class,
            RoutingProvider::class,
            ViewProvider::class,
            ControllerProvider::class,
            InstallerProvider::class,
            ConsoleProvider::class,
        ];
    }

    protected function createContainer(): ContainerInterface
    {
        return new ContainerManager();
    }

    protected function registerErrorHandler(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity) || in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    protected function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return $value === false || $value === null ? null : (string) $value;
    }

    protected function detectProjectDir(): string
    {
        $file = (new ReflectionObject($this))->getFileName();
        $directory = $file !== false ? dirname($file) : (string) getcwd();

        while (true) {
            $composer = $directory . DIRECTORY_SEPARATOR . 'composer.json';

            if (is_file($composer)) {
                $data = json_decode((string) file_get_contents($composer), true);

                if (!is_array($data) || ($data['name'] ?? null) !== 'neophp/framework') {
                    return $directory;
                }
            }

            $parent = dirname($directory);

            if ($parent === $directory) {
                return (string) getcwd();
            }

            $directory = $parent;
        }
    }
}