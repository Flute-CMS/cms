<?php

namespace Flute\Core\Modules\Search\Handlers;

use Flute\Core\Modules\Search\Contracts\SearchResultInterface;

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
     * Массив для хранения дополнительных параметров
     *
     * @var array
     */
    protected array $extraParams = [];

    /**
     * SearchResult constructor.
     * 
     * @param string|null $title
     * @param string|null $url
     * @param string|null $image
     * @param string|null $description
     * @param string|null $type
     * @param array $extraParams Дополнительные параметры
     */
    public function __construct(
        string $title = null,
        string $url = null,
        string $image = null,
        string $description = null,
        string $type = null,
        array $extraParams = []
    ) {
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->image = $image;
        $this->type = $type;
        $this->extraParams = $extraParams;
    }

    // Существующие геттеры и сеттеры...

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
     * Установка дополнительного параметра
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setParam(string $key, $value): self
    {
        $this->extraParams[$key] = $value;
        return $this;
    }

    /**
     * Получение дополнительного параметра
     *
     * @param string $key
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        return $this->extraParams[$key] ?? null;
    }

    /**
     * Удаление дополнительного параметра
     *
     * @param string $key
     * @return self
     */
    public function removeParam(string $key): self
    {
        unset($this->extraParams[$key]);
        return $this;
    }

    /**
     * Получение всех дополнительных параметров
     *
     * @return array
     */
    public function getAllParams(): array
    {
        return $this->extraParams;
    }

    /**
     * Конвертация объекта SearchResult в ассоциативный массив.
     * Включает как основные, так и дополнительные параметры.
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'image' => $this->image,
            'type' => $this->type
        ], $this->extraParams);
    }
}
