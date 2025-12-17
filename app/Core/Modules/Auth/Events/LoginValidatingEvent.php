<?php

namespace Flute\Core\Modules\Auth\Events;

/**
 * Event dispatched before login validation.
 * Modules can add custom validation or reject login.
 */
class LoginValidatingEvent
{
    public const NAME = 'auth.login.validating';

    /**
     * Form data being validated.
     */
    public array $data;

    /**
     * Validation errors. Key = field name, value = error message.
     */
    public array $errors = [];

    /**
     * Whether to stop validation and reject the form.
     */
    public bool $stopValidation = false;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Add a validation error.
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;

        return $this;
    }

    /**
     * Check if validation has errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get a value from form data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Stop validation and reject the form.
     */
    public function reject(string $message): self
    {
        $this->stopValidation = true;
        $this->errors['_global'] = $message;

        return $this;
    }
}
