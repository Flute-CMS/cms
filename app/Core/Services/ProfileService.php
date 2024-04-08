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

    public function addMod(ProfileModInterface $mod)
    {
        $this->mods->set($mod->getKey(), $mod);
    }

    public function addTab(ProfileTabInterface $tab)
    {
        $this->tabs->set($tab->getKey(), $tab);
    }

    public function searchMode(string $key): bool
    {
        return $this->mods->containsKey($key);
    }

    public function searchTab(string $key): bool
    {
        return $this->tabs->containsKey($key);
    }

    public function renderMode(string $key, User $user)
    {
        if (!$this->mods->containsKey($key))
            throw new \RuntimeException("Mod $key not found");

        return $this->mods->get($key)->render($user);
    }

    public function renderTab(string $key, User $user)
    {
        if (!$this->tabs->containsKey($key))
            throw new \RuntimeException("Tab $key not found");

        return $this->tabs->get($key)->render($user);
    }
    public function getMods(): array
    {
        $mods = $this->mods->map(function ($mod) {
            $info = $mod->getSidebarInfo();
            $info['position'] = $info['position'] ?? 0;
            return $info;
        })->toArray();

        uasort($mods, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $mods;
    }

    public function getTabs(): array
    {
        $tabs = $this->tabs->map(function ($tab) {
            $info = $tab->getSidebarInfo();
            $info['position'] = $info['position'] ?? 0;
            return $info;
        })->toArray();

        uasort($tabs, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $tabs;
    }
}