<?php

namespace Flute\Core\Modules\Page\Widgets\Contracts;

interface WidgetInterface
{
    /**
     * Returns the unique name of the widget.
     */
    public function getName(): string;

    /**
     * Returns the widget's icon.
     */
    public function getIcon(): string;

    /**
     * Returns the widget's default settings.
     */
    public function getSettings(): array;

    /**
     * Renders the widget with the given settings.
     */
    public function render(array $settings): string|null;

    /**
     * Renders the form for editing the widget's settings.
     *
     * @param array $settings The current settings of the widget
     */
    public function renderSettingsForm(array $settings): string|bool;

    /**
     * Validates the widget's settings before saving.
     *
     * @param array $input The settings to validate
     * @return true|array True if validation passes, array of errors otherwise
     */
    public function validateSettings(array $input);

    /**
     * Saves the widget's settings.
     */
    public function saveSettings(array $input): array;

    /**
     * Returns the default grid width of the widget.
     */
    public function getDefaultWidth(): int;

    /**
     * Returns the minimum grid width of the widget.
     */
    public function getMinWidth(): int;

    /**
     * Checks if the widget has a settings form.
     */
    public function hasSettings(): bool;

    /**
     * Returns the toolbar buttons for the widget.
     */
    public function getButtons(): array;

    /**
     * Handles a widget action.
     */
    public function handleAction(string $action, ?string $widgetId = null): array;

    /**
     * Returns the category of the widget.
     */
    public function getCategory(): string;
}
