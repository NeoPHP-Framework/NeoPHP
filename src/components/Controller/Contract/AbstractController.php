<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Contract;

use NeoPHP\Component\Container\Helper\Controller\ContainerController;
use NeoPHP\Component\Cookie\Helper\Controller\CookieController;
use NeoPHP\Component\Database\Helper\Controller\DatabaseController;
use NeoPHP\Component\Event\Helper\Controller\EventController;
use NeoPHP\Component\Flash\Helper\Controller\FlashController;
use NeoPHP\Component\Http\Helper\Controller\HttpController;
use NeoPHP\Component\Routing\Helper\Controller\RoutingController;
use NeoPHP\Component\Session\Helper\Controller\SessionController;
use NeoPHP\Component\Validator\Helper\Controller\ValidatorController;
use NeoPHP\Component\View\Helper\Controller\ViewController;
use NeoPHP\Package\Orm\Helper\Controller\OrmController;

abstract class AbstractController implements ControllerInterface
{
    use ContainerController;
    use CookieController;
    use DatabaseController;
    use EventController;
    use FlashController;
    use HttpController;
    use OrmController;
    use RoutingController;
    use SessionController;
    use ValidatorController;
    use ViewController;
}