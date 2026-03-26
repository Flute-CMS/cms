<?php

namespace Flute\Core\Modules\Page\Widgets;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;

class UsersNewWidget extends AbstractWidget
{
    protected const CACHE_TIME = 120;

    public function getName(): string
    {
        return 'widgets.users_new';
    }

    public function getIcon(): string
    {
        return 'ph.regular.user-circle-plus';
    }

    public function render(array $settings): ?string
    {
        $maxDisplay = min($settings['max_display'] ?? 50, 100);
        $cacheKey = 'flute.widget.users_new.' . $maxDisplay;

        $users = cache()->callback(
            $cacheKey,
            static function () use ($maxDisplay) {
                return User::query()
                    ->where('createdAt', '>=', ( new DateTimeImmutable() )->modify('-7 day'))
                    ->where('hidden', false)
                    ->orderBy('createdAt', 'DESC')
                    ->limit($maxDisplay)
                    ->fetchAll();
            },
            self::CACHE_TIME,
        );

        return view('flute::widgets.users-new', [
            'users' => $users,
            'display_type' => $settings['display_type'] ?? 'text',
        ])->render();
    }

    public function getSettings(): array
    {
        return [
            'display_type' => 'text',
            'max_display' => 50,
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
        return 'widgets.users_new_desc';
    }

    public function getCacheTime(): int
    {
        return self::CACHE_TIME;
    }

    public function getDefaultWidth(): int
    {
        return 3;
    }

    public function saveSettings(array $input): array
    {
        return [
            'display_type' => $input['display_type'] ?? 'text',
            'max_display' => min((int) ( $input['max_display'] ?? 50 ), 100),
        ];
    }
}
