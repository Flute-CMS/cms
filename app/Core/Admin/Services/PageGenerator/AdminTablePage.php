<?php

namespace Flute\Core\Admin\Services\PageGenerator;

use Symfony\Component\HttpFoundation\Response;

class AdminTablePage
{
    private string $title;
    private string $header;
    private string $description;
    private string $content;
    private ?string $stylesPath = null;
    private ?string $scriptsPath = null;
    private bool $withAddBtn = false;
    private ?string $btnAddPath = null;

    public function __construct()
    {
        $this->withAddBtn = false;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setHeader(string $header): self
    {
        $this->header = $header;
        return $this;
    }

    public function getHeader(): string
    {
        return $this->header;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setStylesPath(string $stylesPath): self
    {
        $this->stylesPath = $stylesPath;
        return $this;
    }

    public function getStylesPath(): ?string
    {
        return $this->stylesPath;
    }

    public function setScriptsPath(string $scriptsPath): self
    {
        $this->scriptsPath = $scriptsPath;
        return $this;
    }

    public function getScriptsPath(): ?string
    {
        return $this->scriptsPath;
    }

    public function setWithAddBtn(bool $withAddBtn): self
    {
        $this->withAddBtn = $withAddBtn;
        return $this;
    }

    public function getWithAddBtn(): bool
    {
        return $this->withAddBtn;
    }

    public function setBtnAddPath(string $btnAddPath): self
    {
        $this->btnAddPath = $btnAddPath;
        return $this;
    }

    public function getBtnAddPath(): ?string
    {
        return $this->btnAddPath;
    }

    public function generatePage(): Response
    {
        return view("Core/Admin/Http/Views/pages/generator/table", [
            'title' => $this->getTitle(),
            'header' => $this->getHeader(),
            'description' => $this->getDescription(),
            'content' => $this->getContent(),
            'stylesPath' => $this->getStylesPath(),
            'scriptsPath' => $this->getScriptsPath(),
            'withAddBtn' => $this->getWithAddBtn(),
            'btnAddPath' => $this->getBtnAddPath(),
        ]);
    }
}