<?php

namespace Flute\Admin\Packages\User\Listeners;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;
use Flute\Core\Database\Entities\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSearchListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents() : array
    {
        return [
            AdminSearchEvent::NAME => 'onAdminSearch',
        ];
    }

    public function onAdminSearch(AdminSearchEvent $event) : void
    {
        $searchValue = $event->getValue();
        $searchValue = trim($searchValue);

        if (!str_starts_with($searchValue, '/user')) {
            return;
        }

        $searchValue = trim(substr($searchValue, 5)); // Remove /user and trim

        if (empty($searchValue)) {
            $users = User::query()->orderBy('name', 'asc')->limit(10)->fetchAll();
            
            foreach ($users as $user) {
                $event->add($this->createUserSearchResult($user, 1));
            }
            
            return;
        }

        $searchValueLower = mb_strtolower($searchValue, 'UTF-8');

        $users = User::query()->where(function ($query) use ($searchValueLower) {
            $query->orWhere('name', 'LIKE', "%{$searchValueLower}%")
                ->orWhere('login', 'LIKE', "%{$searchValueLower}%")
                ->orWhere('email', 'LIKE', "%{$searchValueLower}%");
        })->limit(10)->fetchAll();

        foreach ($users as $user) {
            $relevance = self::calculateRelevance($searchValueLower, $user);
            $event->add($this->createUserSearchResult($user, $relevance));
        }
    }

    /**
     * Create a search result for a user
     * 
     * @param User $user
     * @param int $relevance
     * @return AdminSearchResult
     */
    protected function createUserSearchResult(User $user, int $relevance): AdminSearchResult
    {
        return new AdminSearchResult(
            $user->name,
            url('admin/users/' . $user->id . '/edit'),
            asset($user->avatar ?? config('profile.default_avatar')),
            __('search.users'),
            $relevance
        );
    }

    /**
     * Calculate relevance score for search result
     * 
     * @param string $searchValue
     * @param User $user
     * @return int
     */
    protected static function calculateRelevance(string $searchValue, User $user) : int
    {
        $relevance = 1;

        $nameLower = mb_strtolower($user->name, 'UTF-8');
        $loginLower = mb_strtolower($user->login ?? '', 'UTF-8');
        $emailLower = mb_strtolower($user->email ?? '', 'UTF-8');

        if ($nameLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($nameLower, $searchValue) === 0) {
            $relevance += 2;
        }

        if ($loginLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($loginLower, $searchValue) === 0) {
            $relevance += 2;
        }

        if ($emailLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($emailLower, $searchValue) === 0) {
            $relevance += 2;
        }

        return $relevance;
    }
}
