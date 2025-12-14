<?php

namespace Flute\Admin\Packages\Server\Listeners;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;
use Flute\Core\Database\Entities\Server;

use function mb_strpos;
use function mb_strtolower;
use function str_starts_with;
use function substr;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function trim;

/**
 * Subscriber for the search event to find servers by the query "/server ..."
 */
class ServerSearchListener implements EventSubscriberInterface
{
    /**
     * Method that returns which events we subscribe to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AdminSearchEvent::NAME => 'onAdminSearch',
        ];
    }

    /**
     * Handler for the search event.
     */
    public function onAdminSearch(AdminSearchEvent $event): void
    {
        $searchValue = trim($event->getValue());

        if (!str_starts_with($searchValue, '/server')) {
            return;
        }

        $searchValue = substr($searchValue, 7);
        $searchValue = trim($searchValue);

        if ($searchValue === '') {
            return;
        }

        $searchValueLower = mb_strtolower($searchValue, 'UTF-8');

        $servers = Server::query()
            ->where(static function ($query) use ($searchValueLower) {
                $query
                    ->orWhere('name', 'LIKE', "%{$searchValueLower}%")
                    ->orWhere('ip', 'LIKE', "%{$searchValueLower}%")
                    ->orWhere('mod', 'LIKE', "%{$searchValueLower}%");
            })
            ->limit(10)
            ->fetchAll();

        foreach ($servers as $server) {
            $relevance = $this->calculateRelevance($searchValueLower, $server);

            $searchResult = new AdminSearchResult(
                $server->name,
                url('admin/servers/' . $server->id . '/edit'),
                $icon = null,
                $description = null,
                $relevance
            );

            $event->add($searchResult);
        }
    }

    /**
     * Calculation of the relevance of the result.
     */
    private function calculateRelevance(string $searchValue, Server $server): int
    {
        $relevance = 1;

        $nameLower = mb_strtolower($server->name, 'UTF-8');
        $ipLower = mb_strtolower($server->ip, 'UTF-8');
        $modLower = mb_strtolower($server->mod, 'UTF-8');

        // The name matches completely => +3, or at the beginning => +2
        if ($nameLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($nameLower, $searchValue) === 0) {
            $relevance += 2;
        }

        // IP
        if ($ipLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($ipLower, $searchValue) === 0) {
            $relevance += 2;
        }

        // Mod
        if ($modLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($modLower, $searchValue) === 0) {
            $relevance += 2;
        }

        return $relevance;
    }
}
