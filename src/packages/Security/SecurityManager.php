<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Security\Authenticator\AuthenticationManager;
use NeoPHP\Package\Security\Authorization\AccessDecisionManager;
use NeoPHP\Package\Security\Authorization\AccessMap;
use NeoPHP\Package\Security\Contract\AbstractSecurity;
use NeoPHP\Package\Security\Firewall\FirewallFactory;
use NeoPHP\Package\Security\Firewall\FirewallMap;
use NeoPHP\Package\Security\Firewall\HttpUtils;
use NeoPHP\Package\Security\Token\TokenStorage;

class SecurityManager extends AbstractSecurity
{
    public function __construct(
        ContainerInterface $container,
        TokenStorage $tokens,
        FirewallMap $map,
        FirewallFactory $factory,
        AuthenticationManager $authentication,
        AccessDecisionManager $decisions,
        AccessMap $accessMap,
        HttpUtils $http,
        bool $enabled = true,
    ) {
        $this->container = $container;
        $this->tokens = $tokens;
        $this->map = $map;
        $this->factory = $factory;
        $this->authentication = $authentication;
        $this->decisions = $decisions;
        $this->accessMap = $accessMap;
        $this->http = $http;
        $this->enabled = $enabled;
    }
}