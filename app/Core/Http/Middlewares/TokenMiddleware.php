<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Database\Entities\ApiKey;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class TokenMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        $token = $request->getAuthorizationBearerToken();

        if (!$token)
            return $this->error();

        $findToken = rep(ApiKey::class)->findOne([
            'key' => $token
        ]);

        if (!$findToken)
            return $this->error();

        $apiUser = new User;
        $apiUser->name = 'API REQUEST';
        $apiUser->id = 'API_ID';

        $apiRole = new Role;
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