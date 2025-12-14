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
     */
    public function __construct(?User $user, string $activeTab, string $type = 'full')
    {
        $this->user = $user;
        $this->activeTab = $activeTab;
        $this->type = $type;
    }

    /**
     * Get the active tab.
     */
    public function getActiveTab(): ?string
    {
        return $this->activeTab;
    }

    /**
     * Set the user.
     *
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the user.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Get the type. (mini, full)
     */
    public function getType(): string
    {
        return $this->type;
    }
}
