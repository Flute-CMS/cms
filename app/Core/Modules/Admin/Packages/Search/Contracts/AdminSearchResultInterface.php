<?php

namespace Flute\Admin\Packages\Search\Contracts;

interface AdminSearchResultInterface
{
    /**
     * Get the title of the search result.
     */
    public function getTitle(): string;

    /**
     * Get the URL of the search result.
     */
    public function getUrl(): string;

    /**
     * Set the title of the search result.
     */
    public function setTitle(string $title): self;

    /**
     * Set the URL of the search result.
     */
    public function setUrl(string $url): self;
}
