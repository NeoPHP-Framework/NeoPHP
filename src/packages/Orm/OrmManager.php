<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Database\Contract\ConnectionInterface;
use NeoPHP\Component\Event\Contract\EventDispatcherInterface;
use NeoPHP\Package\Orm\Contract\AbstractOrm;
use NeoPHP\Package\Orm\Metadata\MetadataFactory;
use NeoPHP\Package\Orm\Proxy\ProxyFactory;

class OrmManager extends AbstractOrm
{
    public function __construct(
        ConnectionInterface $connection,
        MetadataFactory $metadataFactory,
        ProxyFactory $proxyFactory,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?ContainerInterface $container = null,
        string $repositoryNamespace = 'App\\Repository',
    ) {
        $this->connection = $connection;
        $this->metadataFactory = $metadataFactory;
        $this->proxyFactory = $proxyFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
        $this->repositoryNamespace = $repositoryNamespace;
    }
}