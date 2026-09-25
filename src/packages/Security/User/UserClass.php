<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\User;

use NeoPHP\Package\Orm\Contract\ProxyInterface;
use NeoPHP\Package\Security\Contract\UserInterface;

class UserClass
{
    public static function of(UserInterface $user): string
    {
        if ($user instanceof ProxyInterface) {
            $parent = get_parent_class($user);

            return $parent !== false ? $parent : $user::class;
        }

        return $user::class;
    }
}