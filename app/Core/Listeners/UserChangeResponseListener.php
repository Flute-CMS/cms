<?php

namespace Flute\Core\Listeners;

use Flute\Core\Database\Entities\User;
use Flute\Core\Events\ResponseEvent;
use Flute\Core\Events\UserChangedEvent;
use Symfony\Component\HttpFoundation\Response;

class UserChangeResponseListener
{
    /**  */
    private static ?User $changedUser = null;

    public static function onUserChanged(UserChangedEvent $event): void
    {
        self::$changedUser = $event->getUser();
    }

    public static function onResponse(ResponseEvent $event): void
    {
        if (!self::$changedUser) {
            return;
        }

        $response = $event->getResponse();
        self::addUserChangeTrigger($response, self::$changedUser);
    }

    private static function addUserChangeTrigger(Response $response, User $user): void
    {
        $userData = [
            'id' => $user->id,
            'name' => $user->name ?? '',
            'avatar' => $user->avatar ?? '',
            'banner' => $user->banner ?? '',
            'balance' => $user->balance ?? 0,
            'uri' => $user->uri ?? '',
            'email' => $user->email ?? '',
        ];

        $userData['online'] = $user->isOnline();

        $triggerData = json_encode(['user-change' => $userData]);

        if ($response->headers->has('HX-Trigger')) {
            $existingTrigger = $response->headers->get('HX-Trigger');

            $existingData = json_decode($existingTrigger, true);

            if (is_array($existingData)) {
                $existingData['user-change'] = $userData;
                $triggerData = json_encode($existingData);
            }
        }

        $response->headers->set('HX-Trigger', $triggerData);
    }
}
