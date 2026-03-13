<?php

namespace Flute\Core\Modules\Page\Widgets;

use Cycle\Database\Injection\Parameter;
use DateTimeImmutable;
use Flute\Core\Database\Entities\User;

class UsersTodayWidget extends AbstractWidget
{
    protected const CACHE_TIME = 60;

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
        $cacheKey = 'flute.widget.users_today.' . $maxDisplay;

        $userIds = cache()->callback($cacheKey, static function () use ($maxDisplay) {
            $startOfDay = new DateTimeImmutable('today');

            $users = User::query()
                ->where('last_logged', '>=', $startOfDay)
                ->where('hidden', false)
                ->orderBy(['last_logged' => 'DESC'])
                ->limit($maxDisplay)
                ->fetchAll();

            return array_map(static fn ($user) => $user->id, $users);
        }, self::CACHE_TIME);

        $users = !empty($userIds)
            ? User::query()->where('id', 'IN', new Parameter($userIds))->fetchAll()
            : [];

        return view('flute::widgets.users-today', [
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
