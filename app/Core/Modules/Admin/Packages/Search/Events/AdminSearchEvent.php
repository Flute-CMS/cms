<?php

namespace Flute\Admin\Packages\Search\Events;

use Symfony\Contracts\EventDispatcher\Event;
use Flute\Admin\Packages\Search\Services\AdminSearchResult;

/**
 * Event that occurs when searching in the admin panel.
 */
class AdminSearchEvent extends Event
{
    public const NAME = 'admin.search';

    private string $value;

    /** @var AdminSearchResult[] */
    private array $results = [];

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue() : string
    {
        return $this->value;
    }

    /**
     * Add search result.
     */
    public function add(AdminSearchResult $result) : void
    {
        $this->results[] = $result;
    }

    /**
     * @return AdminSearchResult[]
     */
    public function getResults() : array
    {
        return $this->results;
    }
}
