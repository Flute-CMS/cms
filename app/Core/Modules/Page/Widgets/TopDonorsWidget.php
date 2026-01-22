<?php

namespace Flute\Core\Modules\Page\Widgets;

use Cycle\Database\Injection\Parameter;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;

class TopDonorsWidget extends AbstractWidget
{
    protected const CACHE_TIME = 300;

    public function getName(): string
    {
        return 'widgets.top_donors';
    }

    public function getIcon(): string
    {
        return 'ph.regular.heart';
    }

    public function render(array $settings): string
    {
        $limit = (int) ($settings['limit'] ?? 5);
        $topDonors = $this->getTopDonors($limit);

        return view('flute::widgets.top-donors', [
            'users' => $topDonors,
            'settings' => $settings,
        ])->render();
    }

    public function getCategory(): string
    {
        return 'users';
    }

    public function getDefaultWidth(): int
    {
        return 3;
    }

    public function getSettings(): array
    {
        return [
            'display_mode' => 'podium',
            'limit' => 5,
            'show_amount' => true,
        ];
    }

    public function hasSettings(): bool
    {
        return true;
    }

    public function renderSettingsForm(array $settings): string|bool
    {
        return view('flute::widgets.settings.top-donors', ['settings' => $settings])->render();
    }

    public function saveSettings(array $input): array
    {
        return [
            'display_mode' => $input['display_mode'] ?? 'podium',
            'limit' => (int) ($input['limit'] ?? 5),
            'show_amount' => (bool) ($input['show_amount'] ?? true),
        ];
    }

    private function getTopDonors(int $limit = 5): array
    {
        $cacheKey = 'flute.widget.top_donors.' . $limit;

        $cachedData = cache()->callback($cacheKey, static function () use ($limit) {
            $query = PaymentInvoice::query()
                ->where('isPaid', true)
                ->buildQuery();

            $query->columns([
                'user_id',
                new \Cycle\Database\Injection\Fragment('COALESCE(SUM(original_amount), 0) AS total'),
            ]);
            $query->groupBy('user_id');
            $query->orderBy(new \Cycle\Database\Injection\Fragment('COALESCE(SUM(original_amount), 0)'), 'DESC');
            $query->limit($limit);

            $results = $query->fetchAll();

            if (empty($results)) {
                return [];
            }

            return array_map(static fn ($r) => [
                'user_id' => (int) $r['user_id'],
                'total' => (float) $r['total'],
            ], $results);
        }, self::CACHE_TIME);

        if (empty($cachedData)) {
            return [];
        }

        $userIds = array_column($cachedData, 'user_id');
        $userList = User::query()
            ->where('id', 'IN', new Parameter($userIds))
            ->fetchAll();

        $usersById = [];
        foreach ($userList as $u) {
            $usersById[$u->id] = $u;
        }

        $users = [];
        foreach ($cachedData as $data) {
            $u = $usersById[$data['user_id']] ?? null;
            if ($u) {
                $users[] = [
                    'user' => $u,
                    'donated' => $data['total'],
                ];
            }
        }

        return $users;
    }
}
