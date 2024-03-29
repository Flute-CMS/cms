<?php

namespace Flute\Core\Events;
use Flute\Core\Database\Entities\User;

class ProfileRenderEvent
{
    public const NAME = 'flute.profile.render';

    protected ?User $user;
    protected string $activeTab;
    protected array $tabs = [];

    /**
     * ProfileRenderEvent constructor.
     *
     * @param User $user
     * @param string $activeTab
     */
    public function __construct(User $user, string $activeTab)
    {
        $this->user = $user;
        $this->activeTab = $activeTab;
    }

    /**
     * Get the active tab.
     * 
     * @return ?string
     */
    public function getActiveTab() : ?string
    {
        return $this->activeTab;
    }

    /**
     * Set the user.
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user) : self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the user.
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}
