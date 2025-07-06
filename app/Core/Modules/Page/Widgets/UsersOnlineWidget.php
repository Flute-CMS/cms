<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Page\Widgets\AbstractWidget;
use Flute\Core\Database\Repositories\UserRepository;

class UsersOnlineWidget extends AbstractWidget
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
        return 'widgets.users_online';
    }

    public function getIcon(): string
    {
        return 'ph.regular.user-circle';
    }

    public function render(array $settings): string
    {
        $onlineUsers = cache()->callback('online_users', function () {
            return User::query()
                ->where('last_logged', '>=', (new \DateTimeImmutable())->modify('-10 minutes'))
                ->orderBy(['last_logged' => 'DESC'])
                ->limit($settings['max_display'] ?? 10)
                ->fetchAll();
        }, 1800);

        $onlineUsers = array_filter($onlineUsers, static fn($u) => !$u->hidden);

        usort($onlineUsers, static function ($a, $b) {
            return ($b->last_logged?->getTimestamp() ?? 0) <=> ($a->last_logged?->getTimestamp() ?? 0);
        });

        return view('flute::widgets.users-online', [
            'users' => $onlineUsers,
            'display_type' => $settings['display_type'] ?? 'text',
            'max_display' => $settings['max_display'] ?? 10,
        ])->render();
    }

    public function getSettings(): array
    {
        return [
            'display_type' => 'text',
            'max_display' => 10,
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
            'max_display' => (int) ($input['max_display'] ?? 10),
        ];
    }
}
