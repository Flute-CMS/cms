<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\User;

class TopDonorsWidget extends AbstractWidget
{
    public function getName(): string
    {
        return 'widgets.top_donors';
    }

    public function getIcon(): string
    {
        return 'ph.regular.money';
    }

    public function render(array $settings): string
    {
        $topDonors = $this->getTopDonors(5);

        return view('flute::widgets.top-donors', ['users' => $topDonors])->render();
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
     * Get top donors based on their total payment amount
     *
     * @param int $limit The maximum number of donors to return
     * @return array Array of users with their total donation amount
     */
    private function getTopDonors(int $limit = 5): array
    {
        $query = PaymentInvoice::query()
            ->where('isPaid', true)
            ->buildQuery();

        $query->columns([
            'user_id',
            'total' => new \Cycle\Database\Injection\Expression('COALESCE(SUM(original_amount), 0)'),
        ]);
        $query->groupBy('user_id');
        $query->orderBy('total', 'DESC');
        $query->limit($limit);

        $results = $query->fetchAll();

        $users = [];
        foreach ($results as $result) {
            $user = User::findByPK($result['user_id']);
            if ($user) {
                $users[] = [
                    'user' => $user,
                    'donated' => (float) $result['total'],
                ];
            }
        }

        return $users;
    }
}
