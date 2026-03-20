<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Field;

class DatePicker extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::partials.fields.datepicker';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'class' => 'datepicker-wrapper',
        'name' => '',
        'value' => null,
        'enableTime' => false,
        'noCalendar' => false,
        'time24hr' => true,
        'dateFormat' => null,
        'altFormat' => null,
        'minDate' => null,
        'maxDate' => null,
        'defaultDate' => null,
        'mode' => 'single',
        'inline' => false,
        'disabled' => false,
        'placeholder' => null,
        'allowInput' => true,
        'weekNumbers' => false,
        'locale' => null,
        'yoyo' => false,
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'name',
        'id',
        'disabled',
        'placeholder',
        'required',
        'tabindex',
    ];

    public function __construct()
    {
        $this->addBeforeRender(function () {
            if (is_null($this->get('value')) && !$this->get('disableFromRequest')) {
                $this->set('value', request()->input($this->get('name')));
            }
        });
    }

    /**
     * Enable time selection.
     */
    public function enableTime(bool $enableTime = true): self
    {
        return $this->set('enableTime', $enableTime);
    }

    /**
     * Show only time picker (no calendar).
     */
    public function noCalendar(bool $noCalendar = true): self
    {
        return $this->set('noCalendar', $noCalendar);
    }

    /**
     * Use 24-hour time format.
     */
    public function time24hr(bool $time24hr = true): self
    {
        return $this->set('time24hr', $time24hr);
    }

    /**
     * Set the date format for the underlying input value.
     * See https://flatpickr.js.org/formatting/
     */
    public function dateFormat(string $format): self
    {
        return $this->set('dateFormat', $format);
    }

    /**
     * Set the display format shown to the user.
     */
    public function altFormat(string $format): self
    {
        return $this->set('altFormat', $format);
    }

    /**
     * Set minimum selectable date.
     *
     * @param string $date Date string or "today"
     */
    public function minDate(string $date): self
    {
        return $this->set('minDate', $date);
    }

    /**
     * Set maximum selectable date.
     *
     * @param string $date Date string or "today"
     */
    public function maxDate(string $date): self
    {
        return $this->set('maxDate', $date);
    }

    /**
     * Set default date.
     */
    public function defaultDate(string $date): self
    {
        return $this->set('defaultDate', $date);
    }

    /**
     * Set selection mode: "single", "multiple", or "range".
     */
    public function mode(string $mode): self
    {
        return $this->set('mode', $mode);
    }

    /**
     * Shortcut: range mode.
     */
    public function range(): self
    {
        return $this->mode('range');
    }

    /**
     * Shortcut: multiple dates mode.
     */
    public function multiple(): self
    {
        return $this->mode('multiple');
    }

    /**
     * Show calendar inline (always visible).
     */
    public function inline(bool $inline = true): self
    {
        return $this->set('inline', $inline);
    }

    /**
     * Disable the field.
     */
    public function disabled(bool $disabled = true): self
    {
        return $this->set('disabled', $disabled);
    }

    /**
     * Set placeholder text.
     */
    public function placeholder(string $placeholder): self
    {
        return $this->set('placeholder', $placeholder);
    }

    /**
     * Allow manual text input.
     */
    public function allowInput(bool $allowInput = true): self
    {
        return $this->set('allowInput', $allowInput);
    }

    /**
     * Show week numbers.
     */
    public function weekNumbers(bool $weekNumbers = true): self
    {
        return $this->set('weekNumbers', $weekNumbers);
    }

    /**
     * Set locale (e.g. "ru", "en", "de").
     */
    public function locale(string $locale): self
    {
        return $this->set('locale', $locale);
    }

    /**
     * Disable request value auto-fill.
     */
    public function disableFromRequest(): self
    {
        return $this->set('disableFromRequest', true);
    }

    /**
     * Sets the field value.
     *
     * @param mixed $value
     */
    public function value($value): self
    {
        return $this->set(
            'value',
            $this->get('disableFromRequest') ? $value : request()->input($this->get('name'), $value),
        );
    }
}
