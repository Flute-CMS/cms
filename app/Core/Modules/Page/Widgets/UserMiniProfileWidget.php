<?php

namespace Flute\Core\Modules\Page\Widgets;

class UserMiniProfileWidget extends AbstractWidget
{
    /**
     * Returns the unique name of the widget.
     */
    public function getName(): string
    {
        return 'widgets.user_mini_profile';
    }

    /**
     * Returns the icon of the widget.
     */
    public function getIcon(): string
    {
        return 'ph.regular.user-circle';
    }

    /**
     * Renders the widget with specified settings.
     */
    public function render(array $settings): string|null
    {
        $user = user()->getCurrentUser();

        return view('flute::widgets.user-mini-profile', [
            'user' => $user,
            'settings' => $settings,
        ])->render();
    }

    /**
     * Returns the category of the widget.
     */
    public function getCategory(): string
    {
        return 'users';
    }

    /**
     * Returns the default width for gridstack.
     */
    public function getDefaultWidth(): int
    {
        return 3;
    }
}
