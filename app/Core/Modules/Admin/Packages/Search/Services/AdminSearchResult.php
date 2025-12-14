<?php

namespace Flute\Admin\Packages\Search\Services;

use Flute\Admin\Packages\Search\Contracts\AdminSearchResultInterface;

class AdminSearchResult implements AdminSearchResultInterface
{
    protected ?string $url = null;

    protected ?string $title = null;

    protected ?string $icon = "";

    protected ?string $category = "";

    protected int $relevance = 0;

    /**
     * SearchResult constructor.
     */
    public function __construct(?string $title = null, ?string $url = null, ?string $icon = null, ?string $category = null, int $relevance = 0)
    {
        $this->title = $title;
        $this->url = $url;
        $this->icon = $icon;
        $this->category = $category;
        $this->relevance = $relevance;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function getUrl(): string
    {
        return $this->url ?? '';
    }

    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    public function getCategory(): string
    {
        return $this->category ?? '';
    }

    public function getRelevance(): int
    {
        return $this->relevance;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function setRelevance(int $relevance): self
    {
        $this->relevance = $relevance;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'icon' => $this->icon,
            'category' => $this->category,
            'relevance' => $this->relevance,
        ];
    }
}
