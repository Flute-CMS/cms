<?php

namespace Flute\Core\Services;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Contracts\ProfileTabInterface;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\Collection;

class ProfileService
{
    protected Collection $mods;
    protected Collection $tabs;
    protected bool $disableMainInfo = false;

    public function __construct()
    {
        $this->mods = collect();
        $this->tabs = collect();
    }

    public function disableMainInfo()
    {
        $this->disableMainInfo = true;
    }

    public function isMainDisabled()
    {
        return $this->disableMainInfo;
    }

    public function addMod( ProfileModInterface $mod )
    {
        $this->mods->set($mod->getKey(), $mod);
    }

    public function addTab( ProfileTabInterface $tab )
    {
        $this->tabs->set($tab->getKey(), $tab);
    }

    public function searchMode( string $key ) : bool
    {
        return $this->mods->containsKey($key);
    }

    public function searchTab( string $key ) : bool
    {
        return $this->tabs->containsKey($key);
    }

    public function renderMode( string $key, User $user )
    {
        if( !$this->mods->containsKey($key) )
            throw new \RuntimeException("Mod $key not found");

        return $this->mods->get($key)->render($user);
    }

    public function renderTab( string $key, User $user )
    {
        if( !$this->tabs->containsKey($key) )
            throw new \RuntimeException("Tab $key not found");

        return $this->tabs->get($key)->render($user);
    }

    public function getMods(): array
    {
        $result = [];

        foreach( $this->mods as $key => $mod )
            $result[$key] = $mod->getSidebarInfo();

        return $result;
    }

    public function getTabs(): array
    {
        $result = [];

        foreach( $this->tabs as $key => $tab )
            $result[$key] = $tab->getSidebarInfo();

        return $result;
    }
}