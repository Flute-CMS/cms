<?php

namespace Flute\Core\Admin\Support;
use Flute\Core\Admin\Events\AdminSearchEvent;

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

        return $event->toArray();
    }
}
