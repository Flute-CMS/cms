<?php

namespace Flute\Core\Admin\Services\PageGenerator;

/**
 * Class AdminInput
 *
 * Represents a form input field.
 */
class AdminInput
{
    private string $key;
    private string $label;
    private string $description;
    private string $type;
    private bool $required;
    private string $value;
    private bool $hidden;

    /**
     * AdminInput constructor.
     *
     * @param string $key
     * @param string $label
     * @param string $description
     * @param string $type
     * @param bool $required
     * @param string $value
     * @param bool $hidden
     */
    public function __construct(
        string $key,
        string $label,
        string $description = '',
        string $type = 'text',
        bool $required = false,
        string $value = '',
        bool $hidden = false
    ) {
        $this->key = $key;
        $this->label = $label;
        $this->description = $description;
        $this->type = $type;
        $this->required = $required;
        $this->value = $value;
        $this->hidden = $hidden;
    }

    /**
     * Get the key of the input.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set the key of the input.
     *
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Get the label of the input.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set the label of the input.
     *
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * Get the description of the input.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the description of the input.
     *
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Get the type of the input.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type of the input.
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Check if the input is required.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Set the required status of the input.
     *
     * @param bool $required
     */
    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    /**
     * Get the value of the input.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Set the value of the input.
     *
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * Check if the input is hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set the hidden status of the input.
     *
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Convert the input to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'type' => $this->type,
            'required' => $this->required,
            'value' => $this->value,
            'hidden' => $this->hidden,
        ];
    }
}
