<?php

namespace Flute\Core\Services\Concerns\UserService;

use Jenssegers\Agent\Agent;

trait HandlesUserMagic
{
    public function device(): Agent
    {
        if ($this->userDevice !== null) {
            return $this->userDevice;
        }

        $agent = new Agent();
        $agent->setHttpHeaders(request()->headers->all());

        $this->userDevice = $agent;

        return $this->userDevice;
    }

    public function getUserToken(): ?string
    {
        return $this->userToken;
    }

    public function __call(string $name, array $args)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return call_user_func_array([$this->currentUser, $name], $args);
    }

    public function __get(string $name)
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->currentUser->$name ?? null;
    }
}
