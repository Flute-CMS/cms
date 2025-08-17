<?php

namespace Flute\Core\Modules\Search\Events;

use Flute\Core\Modules\Search\Contracts\SearchResultInterface;

class SearchEvent
{
    public const NAME = 'flute.search';

    private string $param;
    private bool $isAdmin = false;
    private array $results = [];

    public function __construct(string $param, bool $isAdmin = false)
    {
        $this->param = $param;
        $this->isAdmin = $isAdmin;
    }

    public function getValue(): string
    {
        return $this->param;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isExists($id, $type)
    {
        foreach ($this->results as $result) {
            if ($result->getId() === $id && $result->getType() === $type) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        $return = [];

        for ($i = 0; $i < count($this->results); $i++) {
            if ($i > 20) {
                break;
            }

            $return[] = $this->results[$i]->toArray();
        }

        return $return;
    }

    public function add(SearchResultInterface $searchResult)
    {
        $this->results[] = $searchResult;
    }
}
