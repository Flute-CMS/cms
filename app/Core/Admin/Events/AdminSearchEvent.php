<?php

namespace Flute\Core\Admin\Events;

use Flute\Core\Admin\Contracts\AdminSearchResultInterface;
use Flute\Core\Contracts\SearchResultInterface;

class AdminSearchEvent
{
    public const NAME = 'flute.admin.search';

    private string $param;
    private array $results = [];

    public function __construct(string $param)
    {
        $this->param = $param;
    }

    public function getValue(): string
    {
        return $this->param;
    }

    public function toArray(): array
    {
        return array_slice(array_map(fn($result) => $result->toArray(), $this->results), 0, 20);
    }

    public function add(AdminSearchResultInterface $searchResult)
    {
        $this->results[] = $searchResult;
    }
}
