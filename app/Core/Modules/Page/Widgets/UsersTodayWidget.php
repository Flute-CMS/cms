<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Page\Widgets\AbstractWidget;
use Flute\Core\Database\Repositories\UserRepository;

class UsersTodayWidget extends AbstractWidget
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct()
    {
        $this->userRepository = rep(User::class);
    }

    public function getName(): string
    {
        return 'widgets.users_today';
    }

    public function getIcon(): string
    {
        return 'ph.regular.users-four';
    }

    public function render(array $settings): string
    {
        $maxDisplay = $settings['max_display'] ?? 10;
        $todayUsers = $this->userRepository->getTodayUsers();

        $todayUsers = array_filter($todayUsers, static fn ($u) => !$u->hidden);

        usort($todayUsers, static function ($a, $b) {
            return ($b->last_logged?->getTimestamp() ?? 0) <=> ($a->last_logged?->getTimestamp() ?? 0);
        });

        if (count($todayUsers) > $maxDisplay) {
            $todayUsers = array_slice($todayUsers, 0, $maxDisplay);
        }

        return view('flute::widgets.users-today', [
            'users' => $todayUsers,
            'display_type' => $settings['display_type'] ?? 'text'
        ])->render();
    }

    public function getSettings(): array
    {
        return [
            'display_type' => 'text',
            'max_display' => 10
        ];
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function renderSettingsForm(array $settings): string|bool
    {
        return view('flute::widgets.settings.users-display', ['settings' => $settings])->render();
    }

    public function getCategory(): string
    {
        return 'users';
    }

    public function getDefaultWidth(): int
    {
        return 3;
    }

    /**
     * Save settings
     */
    public function saveSettings(array $input): array
    {
        return [
            'display_type' => $input['display_type'] ?? 'text',
            'max_display' => (int) ($input['max_display'] ?? 10)
        ];
    }
}
