<?php

namespace Flute\Core\Modules\Auth\Events;

/**
 * Event dispatched when registration form is being rendered.
 * Modules can add custom fields by pushing views to the slots.
 */
class RegisterFormRenderingEvent
{
    public const NAME = 'auth.register.form.rendering';

    /**
     * Views to render before the main form fields.
     */
    public array $beforeFields = [];

    /**
     * Views to render after the main form fields.
     */
    public array $afterFields = [];

    /**
     * Views to render before the submit button.
     */
    public array $beforeSubmit = [];

    /**
     * Views to render after the submit button.
     */
    public array $afterSubmit = [];

    /**
     * Additional data passed to the form.
     */
    public array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Add a view to render before form fields.
     */
    public function addBeforeFields(string $view, array $data = [], int $priority = 100): self
    {
        $this->beforeFields[] = ['view' => $view, 'data' => $data, 'priority' => $priority];

        return $this;
    }

    /**
     * Add a view to render after form fields.
     */
    public function addAfterFields(string $view, array $data = [], int $priority = 100): self
    {
        $this->afterFields[] = ['view' => $view, 'data' => $data, 'priority' => $priority];

        return $this;
    }

    /**
     * Add a view to render before submit button.
     */
    public function addBeforeSubmit(string $view, array $data = [], int $priority = 100): self
    {
        $this->beforeSubmit[] = ['view' => $view, 'data' => $data, 'priority' => $priority];

        return $this;
    }

    /**
     * Add a view to render after submit button.
     */
    public function addAfterSubmit(string $view, array $data = [], int $priority = 100): self
    {
        $this->afterSubmit[] = ['view' => $view, 'data' => $data, 'priority' => $priority];

        return $this;
    }

    /**
     * Render all views for a specific slot.
     */
    public function renderSlot(string $slot): string
    {
        $views = $this->{$slot} ?? [];

        usort($views, static fn ($a, $b) => $a['priority'] <=> $b['priority']);

        $html = '';
        foreach ($views as $item) {
            $rendered = render($item['view'], array_merge($this->data, $item['data']));
            $html .= $rendered instanceof \Illuminate\View\View ? $rendered->render() : (string) $rendered;
        }

        return $html;
    }
}
