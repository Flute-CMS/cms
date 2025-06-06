<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Repositories\UserRepository;

class UsersNewWidget extends AbstractWidget
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct()
    {
        $this->userRepository = rep(User::class);
    }

    public function getName() : string
    {
        return 'widgets.users_new';
    }

    public function getIcon() : string
    {
        return 'ph.regular.user-circle-plus';
    }

    public function render(array $settings) : string|null
    {
        $maxDisplay = $settings['max_display'] ?? 10;
        $newUsers = $this->userRepository->getLatestUsers($maxDisplay);

        return view('flute::widgets.users-new', [
            'users' => $newUsers,
            'display_type' => $settings['display_type'] ?? 'text'
        ])->render();
    }

    public function getSettings() : array
    {
        return [
            'display_type' => 'text',
            'max_display' => 10
        ];
    }

    public function hasSettings() : bool
    {
        return true;
    }

    public function renderSettingsForm(array $settings) : string|bool
    {
        return view('flute::widgets.settings.users-display', ['settings' => $settings])->render();
    }

    public function getCategory() : string
    {
        return 'users';
    }

    public function getDefaultWidth() : int
    {
        return 3;
    }

    /**
     * Save settings
     */
    public function saveSettings(array $input) : array
    {
        return [
            'display_type' => $input['display_type'] ?? 'text',
            'max_display' => (int) ($input['max_display'] ?? 10)
        ];
    }
}
