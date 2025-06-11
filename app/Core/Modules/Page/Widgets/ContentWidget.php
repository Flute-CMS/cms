<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Modules\Page\Widgets\Contracts\WidgetInterface;

class ContentWidget implements WidgetInterface
{
    public function getCategory(): string
    {
        return 'system';
    }

    public function getName(): string
    {
        return __('widgets.content.name');
    }

    public function getDescription(): string
    {
        return __('widgets.content.description');
    }

    public function getIcon(): string
    {
        return 'ph.regular.file-text';
    }

    public function render(array $settings = []): string
    {
        return view('flute::widgets.content-widget')->render();
    }

    public function hasSettings(): bool
    {
        return false;
    }

    public function getSettings(): array
    {
        return [];
    }

    public function renderSettingsForm(array $settings): false
    {
        return false;
    }

    public function validateSettings(array $input)
    {
        return true;
    }

    public function saveSettings(array $input): array
    {
        return [];
    }

    public function getDefaultWidth(): int
    {
        return 12;
    }

    public function getMinWidth(): int
    {
        return 4;
    }

    public function getButtons(): array
    {
        return [];
    }

    public function handleAction(string $action, ?string $widgetId = null): array
    {
        return [];
    }

    public function isRemovable(): bool
    {
        return false;
    }

    public function isVisible(): bool
    {
        return false;
    }
}
