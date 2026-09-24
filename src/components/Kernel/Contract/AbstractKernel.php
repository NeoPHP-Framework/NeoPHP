<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Contract;

use Composer\InstalledVersions;
use ErrorException;
use NeoPHP\Component\Asset\Provider\AssetProvider;
use NeoPHP\Component\Config\Provider\ConfigProvider;
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
use NeoPHP\Component\Logger\Contract\LoggerManagerInterface;
use NeoPHP\Component\Logger\Provider\LoggerProvider;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\Routing\Provider\RoutingProvider;
use NeoPHP\Component\View\Provider\ViewProvider;
use NeoPHP\Package\Dotenv\DotenvManager;
use NeoPHP\Package\Dotenv\Provider\DotenvProvider;
use NeoPHP\Package\Yaml\Provider\YamlProvider;
use NeoPHP\Process\Console\Provider\ConsoleProvider;
use NeoPHP\Process\Installer\Provider\InstallerProvider;
use ReflectionObject;
use Throwable;

abstract class AbstractKernel implements KernelInterface
{
    public const VERSION = 'dev';

    public const PACKAGE = 'neophp/framework';

    protected string $rootPath;

    protected string $environment;

    protected bool $debug;

    protected ?ContainerInterface $container = null;

    protected bool $booted = false;

    public function __construct(?string $environment = null, ?bool $debug = null, ?string $rootPath = null)
    {
        $this->rootPath = rtrim($rootPath ?? $this->detectRootPath(), '/\\');

        if ($environment !== null) {
            $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $environment;
        }

        $this->loadEnvironment();

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
        $container->instance(ConfigProvider::PARAMETERS_ID, $this->getParameters());

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

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getConfigPath(): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'config';
    }

    public function getPublicPath(): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'public';
    }

    public function getTemplatesPath(): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . 'templates';
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getVersion(): string
    {
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled(static::PACKAGE)) {
            return (string) InstalledVersions::getPrettyVersion(static::PACKAGE);
        }

        return static::VERSION;
    }

    public function getParameters(): array
    {
        return [
            'kernel.root_path' => $this->rootPath,
            'kernel.config_path' => $this->getConfigPath(),
            'kernel.public_path' => $this->getPublicPath(),
            'kernel.templates_path' => $this->getTemplatesPath(),
            'kernel.environment' => $this->environment,
            'kernel.debug' => $this->debug,
            'kernel.version' => $this->getVersion(),
        ];
    }

    protected function handleException(Throwable $exception, Request $request): Response
    {
        $manager = $this->container !== null && $this->container->has(ExceptionManager::class)
            ? $this->container->get(ExceptionManager::class)
            : new ExceptionManager($this->debug);

        $status = $manager->getStatusCode($exception);
        $headers = $manager->getHeaders($exception);

        $this->logException($exception, $request, $status);

        if ($request->wantsJson() || $request->isJson()) {
            return new JsonResponse($manager->renderJson($exception), $status, $headers);
        }

        return new Response($manager->render($exception), $status, $headers);
    }

    protected function logException(Throwable $exception, Request $request, int $status): void
    {
        if ($status < 500 || $this->container === null || !$this->container->has(LoggerManagerInterface::class)) {
            return;
        }

        try {
            $logger = $this->container->get(LoggerManagerInterface::class);

            if ($logger->hasChannel('framework')) {
                $logger->channel('framework')->critical('Uncaught {class}: {message} ({method} {path})', [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'method' => $request->getMethod(),
                    'path' => $request->getPath(),
                    'exception' => $exception,
                ]);
            }
        } catch (Throwable) {
        }
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
            DotenvProvider::class,
            ConfigProvider::class,
            LoggerProvider::class,
            HttpProvider::class,
            RoutingProvider::class,
            AssetProvider::class,
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

    protected function loadEnvironment(): void
    {
        (new DotenvManager())->loadEnv($this->rootPath);
    }

    protected function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return $value === false || $value === null ? null : (string) $value;
    }

    protected function detectRootPath(): string
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