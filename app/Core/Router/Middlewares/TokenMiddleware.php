<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class TokenMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        $token = $request->getAuthorizationBearerToken();

        if (!$token) {
            return $next($request);
        }

        $findToken = ApiKey::findOne(['key' => $token]);

        if (!$findToken) {
            return $this->error()->badRequest();
        }

        $apiUser = new User();
        $apiUser->name = 'API REQUEST';
        $apiUser->id = 0;

        $apiRole = new Role();
        $apiRole->name = 'API ROLE';
        $apiRole->priority = 100;

        foreach ($findToken->permissions as $perm) {
            $apiRole->addPermission($perm);
        }

        $apiUser->addRole($apiRole);

        user()->setCurrentUser($apiUser);

        return $next($request);
    }
}
