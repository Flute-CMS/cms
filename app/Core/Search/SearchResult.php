<?php

namespace Flute\Core\Search;

use Flute\Core\Contracts\SearchResultInterface;

/**
 * SearchResult is the class implementation of SearchResultInterface
 */
class SearchResult implements SearchResultInterface
{
    protected $id = null;
    protected ?string $title = null;
    protected ?string $url = null;
    protected ?string $description = null;
    protected ?string $image = null;
    protected ?string $type = null;

    /**
     * SearchResult constructor.
     * @param string|null $title
     * @param string|null $url
     * @param string|null $image
     * @param string|null $description
     * @param string|null $type
     */
    public function __construct(string $title = null, string $url = null, string $image = null, string $description = null, string $type = null)
    {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->image = $image;
        $this->type = $type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
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

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Convert the SearchResult object to an associative array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'image' => $this->image
        ];
    }
}