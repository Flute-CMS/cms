<?php

namespace Flute\Admin\Packages\Pages\Listeners;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;
use Flute\Core\Database\Entities\Page;

use function mb_strpos;
use function mb_strtolower;
use function str_starts_with;
use function substr;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function trim;

/**
 * Subscriber for the search event to find pages by the query "/page ..."
 */
class PageSearchListener implements EventSubscriberInterface
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

        $isSlashCommand = str_starts_with($searchValue, '/page');

        if ($isSlashCommand) {
            $searchValue = substr($searchValue, 5);
            $searchValue = trim($searchValue);

            if ($searchValue === '') {
                $pages = Page::query()->orderBy('title', 'asc')->limit(10)->fetchAll();

                foreach ($pages as $page) {
                    $event->add($this->createPageSearchResult($page, 1));
                }

                return;
            }
        } else {
            if (strlen($searchValue) < 2) {
                return;
            }
        }

        $searchValueLower = mb_strtolower($searchValue, 'UTF-8');

        $pages = Page::query()
            ->where(static function ($query) use ($searchValueLower) {
                $query
                    ->orWhere('title', 'LIKE', "%{$searchValueLower}%")
                    ->orWhere('route', 'LIKE', "%{$searchValueLower}%")
                    ->orWhere('description', 'LIKE', "%{$searchValueLower}%");
            })
            ->limit(10)
            ->fetchAll();

        foreach ($pages as $page) {
            $relevance = $this->calculateRelevance($searchValueLower, $page);
            $event->add($this->createPageSearchResult($page, $relevance));
        }
    }

    /**
     * Create a search result for a page
     */
    protected function createPageSearchResult(Page $page, int $relevance): AdminSearchResult
    {
        return new AdminSearchResult(
            $page->title,
            url('admin/pages/' . $page->id . '/edit'),
            'ph.regular.file-text',
            __('search.category_pages'),
            $relevance
        );
    }

    /**
     * Calculation of the relevance of the result.
     */
    private function calculateRelevance(string $searchValue, Page $page): int
    {
        $relevance = 1;

        $titleLower = mb_strtolower($page->title, 'UTF-8');
        $routeLower = mb_strtolower($page->route, 'UTF-8');
        $descriptionLower = mb_strtolower($page->description ?? '', 'UTF-8');

        // The title matches completely => +3, or at the beginning => +2
        if ($titleLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($titleLower, $searchValue) === 0) {
            $relevance += 2;
        }

        // Route
        if ($routeLower === $searchValue) {
            $relevance += 3;
        } elseif (mb_strpos($routeLower, $searchValue) === 0) {
            $relevance += 2;
        }

        // Description
        if (mb_strpos($descriptionLower, $searchValue) !== false) {
            $relevance += 1;
        }

        return $relevance;
    }
}
