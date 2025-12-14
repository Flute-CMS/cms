<?php

namespace Flute\Core\Modules\Search\Handlers;

use Flute\Core\Modules\Search\Events\SearchEvent;

/**
 * SearchHandler is responsible for emitting search events.
 */
class SearchHandler
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
        /** @var SearchEvent $event */
        $event = events()->dispatch(new SearchEvent($param), SearchEvent::NAME);

        return $event->toArray();
    }
}
