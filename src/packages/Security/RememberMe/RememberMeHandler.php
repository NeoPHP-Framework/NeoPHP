<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\RememberMe;

use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Package\Security\Authentication\Passport;
use NeoPHP\Package\Security\Contract\PasswordAuthenticatedUserInterface;
use NeoPHP\Package\Security\Contract\UserInterface;
use NeoPHP\Package\Security\Exception\SecurityException;
use NeoPHP\Package\Security\Firewall\HttpUtils;
use NeoPHP\Package\Security\User\UserClass;

class RememberMeHandler
{
    public const DEFAULT_OPTIONS = [
        'name' => 'REMEMBERME',
        'lifetime' => 604800,
        'path' => '/',
        'domain' => null,
        'secure' => 'auto',
        'httponly' => true,
        'samesite' => 'Lax',
        'parameter' => '_remember_me',
        'always' => false,
    ];

    protected array $options;

    public function __construct(protected HttpUtils $http, protected string $secret, array $options = [])
    {
        if ($secret === '') {
            throw new SecurityException('The remember-me feature needs a secret: define APP_SECRET in .env.');
        }

        $this->options = array_replace(static::DEFAULT_OPTIONS, array_intersect_key($options, static::DEFAULT_OPTIONS));
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function isRequested(Passport $passport): bool
    {
        return (bool) $this->options['always'] || $passport->isRememberMe();
    }

    public function createCookie(UserInterface $user): void
    {
        $expires = time() + (int) $this->options['lifetime'];
        $identifier = $user->getUserIdentifier();
        $value = self::encode($identifier) . ':' . $expires . ':' . $this->signature($identifier, $expires, $user);

        $this->http->getCookies()->set((string) $this->options['name'], $value, $this->cookieOptions() + ['lifetime' => (int) $this->options['lifetime']]);
    }

    public function clearCookie(): void
    {
        $this->http->getCookies()->remove((string) $this->options['name'], $this->cookieOptions());
    }

    public function hasCookie(Request $request): bool
    {
        return is_string($request->cookies->get((string) $this->options['name']));
    }

    public function parse(Request $request): ?array
    {
        $value = $request->cookies->get((string) $this->options['name']);

        if (!is_string($value) || substr_count($value, ':') !== 2) {
            return null;
        }

        [$identifier, $expires, $signature] = explode(':', $value);
        $identifier = self::decode($identifier);

        if ($identifier === null || $identifier === '' || !ctype_digit($expires) || $signature === '') {
            return null;
        }

        return ['identifier' => $identifier, 'expires' => (int) $expires, 'signature' => $signature];
    }

    public function isValid(UserInterface $user, int $expires, string $signature): bool
    {
        return $expires > time() && hash_equals($this->signature($user->getUserIdentifier(), $expires, $user), $signature);
    }

    protected function signature(string $identifier, int $expires, UserInterface $user): string
    {
        $password = $user instanceof PasswordAuthenticatedUserInterface ? (string) $user->getPassword() : '';

        return hash_hmac('sha256', UserClass::of($user) . '|' . $identifier . '|' . $expires . '|' . $password, $this->secret);
    }

    protected function cookieOptions(): array
    {
        return [
            'path' => (string) $this->options['path'],
            'domain' => $this->options['domain'],
            'secure' => $this->options['secure'],
            'httponly' => (bool) $this->options['httponly'],
            'samesite' => (string) $this->options['samesite'],
        ];
    }

    protected static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function decode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}