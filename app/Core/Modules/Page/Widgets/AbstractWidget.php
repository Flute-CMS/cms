<?php

namespace Flute\Core\Modules\Page\Widgets;

use Flute\Core\Modules\Page\Widgets\Contracts\WidgetInterface;

/**
 * Base abstract widget class.
 */
abstract class AbstractWidget implements WidgetInterface
{
    /**
     * Returns the unique name of the widget.
     */
    abstract public function getName(): string;

    /**
     * Returns the icon of the widget.
     */
    abstract public function getIcon(): string;

    /**
     * Returns the widget's default settings.
     */
    public function getSettings(): array
    {
        return [];
    }

    /**
     * Renders the widget with specified settings.
     */
    abstract public function render(array $settings): string|null;

    /**
     * Renders the widget's settings form.
     *
     * @param array $settings The current settings of the widget
     */
    public function renderSettingsForm(array $settings): string|bool
    {
        return false;
    }

    /**
     * Validates the widget's settings before saving.
     *
     * @param array $input The settings to validate
     * @return true|array True if validation passes, array of errors otherwise
     */
    public function validateSettings(array $input)
    {
        return true;
    }

    /**
     * Checks if the widget has settings.
     */
    public function hasSettings(): bool
    {
        return false;
    }

    /**
     * Saves the widget's settings.
     */
    public function saveSettings(array $input): array
    {
        return [];
    }

    /**
     * Returns the default width for gridstack.
     */
    public function getDefaultWidth(): int
    {
        return 6;
    }

    /**
     * Returns the minimum width for gridstack.
     */
    public function getMinWidth(): int
    {
        return 2;
    }

    /**
     * Returns the toolbar buttons for this widget.
     */
    public function getButtons(): array
    {
        return [];
    }

    /**
     * Handles custom widget actions.
     */
    public function handleAction(string $action, ?string $widgetId = null): array
    {
        return [];
    }

    /**
     * Returns the category of the widget.
     */
    public function getCategory(): string
    {
        return 'general';
    }
}
