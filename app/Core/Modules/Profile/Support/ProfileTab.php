<?php

namespace Flute\Core\Modules\Profile\Support;

use Flute\Core\Database\Entities\User;

abstract class ProfileTab
{
    /**
     * Returns the unique identifier of the tab.
     */
    abstract public function getId(): string;

    /**
     * Returns the URL path associated with the tab.
     */
    abstract public function getPath(): string;

    /**
     * Returns the title of the tab.
     */
    abstract public function getTitle(): string;

    /**
     * Returns the content of the tab.
     *
     * @return string|\Illuminate\View\View
     */
    abstract public function getContent(User $user);

    /**
     * Returns the order (priority) of the tab.
     *
     * Lower numbers indicate higher priority.
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
