<?php

namespace Flute\Core\Services;

class BreadcrumbService
{
    protected array $breadcrumbs = [];

    public function add(string $title, ?string $url = null): self
    {
        $this->breadcrumbs[] = [
            'title' => $title,
            'url' => $url,
        ];

        return $this;
    }

    public function all(): array
    {
        return $this->breadcrumbs;
    }
}
