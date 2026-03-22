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

        $users = cache()->callback(
            $cacheKey,
            static function () use ($maxDisplay) {
                return User::query()
                    ->where('last_logged', '>=', ( new DateTimeImmutable() )->modify('-10 minutes'))
                    ->where('hidden', false)
                    ->orderBy(['last_logged' => 'DESC'])
                    ->limit($maxDisplay)
                    ->fetchAll();
            },
            self::CACHE_TIME,
        );

        return view('flute::widgets.users-online', [
            'users' => $users,
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

    public function getDescription(): string
    {
        return 'widgets.users_online_desc';
    }

    public function getCacheTime(): int
    {
        return self::CACHE_TIME;
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
            'max_display' => (int) ( $input['max_display'] ?? 10 ),
        ];
    }
}
