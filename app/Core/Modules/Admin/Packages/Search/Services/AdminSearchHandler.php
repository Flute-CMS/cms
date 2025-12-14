<?php

namespace Flute\Admin\Packages\Search\Services;

use Flute\Admin\Packages\Search\Events\AdminSearchEvent;

class AdminSearchHandler
{
    /**
     * Emit a search event and return its results.
     *
     * @param string $param Search parameter
     *
     * @return array The results of the emitted search event.
     */
    public function emit(string $param): array
    {
        /** @var AdminSearchEvent $event */
        $event = events()->dispatch(new AdminSearchEvent($param), AdminSearchEvent::NAME);

        $results = $event->getResults();

        usort($results, static fn (AdminSearchResult $a, AdminSearchResult $b) => $b->getRelevance() <=> $a->getRelevance());

        return $results;
    }
}
