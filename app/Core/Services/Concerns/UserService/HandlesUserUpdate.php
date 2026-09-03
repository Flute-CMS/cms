<?php

namespace Flute\Core\Services\Concerns\UserService;

use Flute\Core\Database\Entities\User;
use Flute\Core\Events\UserChangedEvent;
use InvalidArgumentException;
use Throwable;

trait HandlesUserUpdate
{
    /**
     * @throws Throwable
     */
    public function updateUser(User $user): void
    {
        transaction($user)->run();

        events()->dispatch(new UserChangedEvent($user), UserChangedEvent::NAME);
    }

    /**
     * @throws Throwable
     */
    public function updateUserProperty(User $user, string $property, $value): void
    {
        if (!property_exists($user, $property)) {
            throw new InvalidArgumentException("Property {$property} does not exist on User entity");
        }

        $user->$property = $value;
        $this->updateUser($user);
    }
}
