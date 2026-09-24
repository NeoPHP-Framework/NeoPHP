<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Contract;

use NeoPHP\Component\Container\Helper\Controller\ContainerController;
use NeoPHP\Component\Http\Helper\Controller\HttpController;
use NeoPHP\Component\Routing\Helper\Controller\RoutingController;
use NeoPHP\Component\View\Helper\Controller\ViewController;

abstract class AbstractController implements ControllerInterface
{
    use ContainerController;
    use HttpController;
    use RoutingController;
    use ViewController;
}