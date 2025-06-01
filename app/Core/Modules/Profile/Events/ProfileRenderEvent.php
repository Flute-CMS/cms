<?php

namespace Flute\Core\Modules\Profile\Events;

use Flute\Core\Database\Entities\User;

class ProfileRenderEvent
{
    public const NAME = 'flute.profile.render';

    protected ?User $user;
    protected string $activeTab;
    protected array $tabs = [];
    protected string $type;

    /**
     * ProfileRenderEvent constructor.
     *
     * @param User|null $user
     * @param string $activeTab
     * @param string $type
     */
    public function __construct(?User $user, string $activeTab, string $type = 'full')
    {
        $this->user = $user;
        $this->activeTab = $activeTab;
        $this->type = $type;
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
    public function getUser() : ?User
    {
        return $this->user;
    }

    /**
     * Get the type. (mini, full)
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }
}
