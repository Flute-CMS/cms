<?php

namespace Flute\Admin\Packages\Server\Listeners;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;
use Flute\Core\Database\Entities\Server;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function mb_strpos;
use function mb_strtolower;
use function str_starts_with;
use function substr;
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

        $isSlashCommand = str_starts_with($searchValue, '/server');

        if ($isSlashCommand) {
            $searchValue = substr($searchValue, 7);
            $searchValue = trim($searchValue);

            if ($searchValue === '') {
                $servers = Server::query()
                    ->orderBy('name', 'asc')
                    ->limit(10)
                    ->fetchAll();

                foreach ($servers as $server) {
                    $event->add($this->createServerSearchResult($server, 1));
                }

                return;
            }
        } else {
            if (strlen($searchValue) < 2) {
                return;
            }
        }

        $searchValueLower = mb_strtolower($searchValue, 'UTF-8');
        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchValueLower);

        $servers = Server::query()
            ->where(static function ($query) use ($escapedSearch) {
                $query
                    ->orWhere('name', 'LIKE', "%{$escapedSearch}%")
                    ->orWhere('ip', 'LIKE', "%{$escapedSearch}%")
                    ->orWhere('mod', 'LIKE', "%{$escapedSearch}%");
            })
            ->limit(10)
            ->fetchAll();

        foreach ($servers as $server) {
            $relevance = $this->calculateRelevance($searchValueLower, $server);
            $event->add($this->createServerSearchResult($server, $relevance));
        }
    }

    /**
     * Create a search result for a server
     */
    protected function createServerSearchResult(Server $server, int $relevance): AdminSearchResult
    {
        return new AdminSearchResult(
            $server->name,
            url('admin/servers/' . $server->id . '/edit'),
            'ph.regular.hard-drives',
            __('search.category_servers'),
            $relevance,
        );
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
