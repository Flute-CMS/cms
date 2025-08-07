<?php

namespace Flute\Core\Modules\Search\Contracts;

/**
 * SearchResultInterface defines the basic contract for a search result entity
 */
interface SearchResultInterface
{
    /**
     * Get the ID of the search result.
     */
    public function getId();

    /**
     * Get the title of the search result.
     */
    public function getTitle(): string;

    /**
     * Get the URL of the search result.
     */
    public function getUrl(): string;

    /**
     * Get the description of the search result.
     */
    public function getDescription(): ?string;

    /**
     * Get the image URL of the search result.
     */
    public function getImage(): ?string;

    /**
     * Get the type of the search result.
     */
    public function getType(): string;

    /**
     * Set the ID of the search result.
     */
    public function setId($id): self;

    /**
     * Set the title of the search result.
     */
    public function setTitle(string $title): self;

    /**
     * Set the URL of the search result.
     */
    public function setUrl(string $url): self;

    /**
     * Set the description of the search result.
     */
    public function setDescription(string $description): self;

    /**
     * Set the image URL of the search result.
     */
    public function setImage(string $image): self;

    /**
     * Set the type of the search result.
     */
    public function setType(string $type): self;
}
