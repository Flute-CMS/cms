<?php

namespace Flute\Core\Listeners;

use Flute\Core\Database\Entities\Role;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;

class DefaultRoleListener
{
    public static function handle(UserRegisteredEvent $event)
    {
        $user = $event->getUser();

        if ($user->isTemporary()) {
            return;
        }

        $roleId = config('auth.default_role');

        if (!$roleId) {
            return;
        }

        $role = Role::findByPK($roleId);

        if (!$role) {
            config()->set('auth.default_role', null);
            config()->save();

            return;
        }

        if ($user->hasRole($role->name)) {
            return;
        }

        $user->addRole($role);
        $user->save();
    }
}
