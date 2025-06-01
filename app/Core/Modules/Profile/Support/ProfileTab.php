<?php

namespace Flute\Core\Modules\Profile\Support;

use Flute\Core\Database\Entities\User;

abstract class ProfileTab
{
    /**
     * Returns the unique identifier of the tab.
     *
     * @return string
     */
    abstract public function getId(): string;

    /**
     * Returns the URL path associated with the tab.
     *
     * @return string
     */
    abstract public function getPath(): string;

    /**
     * Returns the title of the tab.
     *
     * @return string
     */
    abstract public function getTitle(): string;

    /**
     * Returns the content of the tab.
     *
     * @param User $user
     * @return string|\Illuminate\View\View
     */
    abstract public function getContent(User $user);

    /**
     * Returns the order (priority) of the tab.
     *
     * Lower numbers indicate higher priority.
     *
     * @return int
     */
    public function getOrder(): int
    {
        return 100; // Default priority
    }

    public function getIcon(): ?string
    {
        return null;
    }

    /**
     * Checks if the user can view this tab.
     *
     * @param User $user
     * @return bool
     */
    public function canView(User $user): bool
    {
        return true;
    }

    public function getDescription(): ?string
    {
        return null;
    }
}
