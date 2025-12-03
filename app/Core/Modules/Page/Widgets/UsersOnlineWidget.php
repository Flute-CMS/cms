<?php

namespace Flute\Core\Modules\Page\Widgets;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Repositories\UserRepository;

class UsersOnlineWidget extends AbstractWidget
{
    protected const CACHE_TIME = 30;

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
        $maxDisplay = $settings['max_display'] ?? 10;
        $cacheKey = 'flute.widget.users_online.' . $maxDisplay;

        $onlineUsers = cache()->callback($cacheKey, static function () use ($maxDisplay) {
            $users = User::query()
                ->where('last_logged', '>=', (new DateTimeImmutable())->modify('-10 minutes'))
                ->where('hidden', false)
                ->orderBy(['last_logged' => 'DESC'])
                ->limit($maxDisplay)
                ->fetchAll();

            usort($users, static fn ($a, $b) => ($b->last_logged?->getTimestamp() ?? 0) <=> ($a->last_logged?->getTimestamp() ?? 0));

            return $users;
        }, self::CACHE_TIME);

        return view('flute::widgets.users-online', [
            'users' => $onlineUsers,
            'display_type' => $settings['display_type'] ?? 'text',
            'max_display' => $maxDisplay,
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
