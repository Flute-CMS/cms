<?php

namespace Flute\Core\Template;

class TemplateVariables
{
    /**
     * Default variables of each template.
     * 
     * @var array
     */
    protected array $variables = [
        // Цвета
        'color-text' => '#FFFFFF',
        'color-text-inverse' => '#0B0B0B',
        'color-primary' => '#BAFF68',
        'color-primary-light' => '#bbff681f',
        'color-secondary' => '#AD68FF',
        'color-secondary-light' => '#ad68ff4f',
        'color-card' => '#101010',
        'color-bg' => '#0B0B0B',
        'color-gray' => '#696969',
        'color-inactive' => '#5F5F5F',
        'color-disabled' => '#191919',
        'color-success' => '#65FF7E',
        'color-success-light' => 'rgba(101, 255, 126, 0.2)',
        'color-error' => '#F14949',
        'color-error-light' => 'rgba(241, 73, 73, 0.2)',
        'color-warning' => '#FFC046',
        'color-warning-light' => 'rgba(255, 192, 70, 0.2)',

        'color-white-20' => 'rgba(255, 255, 255, 0.2)',
        'color-white-10' => 'rgba(255, 255, 255, 0.1)',
        'color-white-5' => 'rgba(255, 255, 255, 0.05)',
        'color-white-3' => 'rgba(255, 255, 255, 0.03)',

        'color-modal-bg' => '#191919e3',

        // Шрифты
        'font-primary' => '\'Montserrat\', \'Open Sans\', system-ui, -apple-system, "Segoe UI", "Roboto", "Ubuntu", "Cantarell", "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
        'font-secondary' => '\'SF Pro Display\', \'Montserrat\', system-ui, -apple-system, "Segoe UI", "Roboto", "Ubuntu", "Cantarell", "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
        'font-line-height' => 1.5,

        // Размеры
        'font-size-large' => '20px',
        'font-size-base' => '16px',
        'font-size-small' => '14px',

        // Прочее
        'border-radius' => '25px',
        'border-radius-el' => '14px',
        'transition' => '.3s',

        // Отступы
        'spacing-xs' => '5px',
        'spacing-sm' => '10px',
        'spacing-md' => '15px',
        'spacing-lg' => '20px',
        'spacing-xl' => '30px'
    ];

    protected array $defaultVariables = [
        // Цвета
        'color-text' => '#FFFFFF',
        'color-text-inverse' => '#0B0B0B',
        'color-primary' => '#BAFF68',
        'color-primary-light' => '#bbff681f',
        'color-secondary' => '#AD68FF',
        'color-secondary-light' => '#ad68ff4f',
        'color-card' => '#101010',
        'color-bg' => '#0B0B0B',
        'color-gray' => '#696969',
        'color-inactive' => '#5F5F5F',
        'color-disabled' => '#191919',
        'color-success' => '#65FF7E',
        'color-success-light' => 'rgba(101, 255, 126, 0.2)',
        'color-error' => '#F14949',
        'color-error-light' => 'rgba(241, 73, 73, 0.2)',
        'color-warning' => '#FFC046',
        'color-warning-light' => 'rgba(255, 192, 70, 0.2)',

        'color-white-20' => 'rgba(255, 255, 255, 0.2)',
        'color-white-10' => 'rgba(255, 255, 255, 0.1)',
        'color-white-5' => 'rgba(255, 255, 255, 0.05)',
        'color-white-3' => 'rgba(255, 255, 255, 0.03)',

        'color-modal-bg' => '#191919e3',

        // Шрифты
        'font-primary' => '\'Montserrat\', \'Open Sans\', system-ui, -apple-system, "Segoe UI", "Roboto", "Ubuntu", "Cantarell", "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
        'font-secondary' => '\'SF Pro Display\', \'Montserrat\', system-ui, -apple-system, "Segoe UI", "Roboto", "Ubuntu", "Cantarell", "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
        'font-line-height' => 1.5,

        // Размеры
        'font-size-large' => '20px',
        'font-size-base' => '16px',
        'font-size-small' => '14px',

        // Прочее
        'border-radius' => '25px',
        'border-radius-el' => '14px',
        'transition' => '.3s',

        // Отступы
        'spacing-xs' => '5px',
        'spacing-sm' => '10px',
        'spacing-md' => '15px',
        'spacing-lg' => '20px',
        'spacing-xl' => '30px'
    ];
    
    /**
     * Set all to default variables.
     */
    public function setAllToDefault(): void
    {
        $this->variables = $this->defaultVariables;
    }
    
    /**
     * Get all template variables.
     * 
     * @return array The array of template variables.
     */
    public function getAll(): array
    {
        return $this->variables;
    }

    /**
     * Set a template variable.
     * 
     * @param string $name The name of the variable.
     * @param mixed $value The value of the variable.
     */
    public function change(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Replace all template variables.
     * 
     * @param array $variables The new array of variables.
     * @return self
     */
    public function set(array $variables): self
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Add multiple variables to the template without overwriting existing ones.
     * 
     * @param array $variables Array of variables to add.
     * @return self
     */
    public function addArray(array $variables): self
    {
        $this->variables = array_merge($this->variables, $variables);
        return $this;
    }

    /**
     * Get a variable value.
     * 
     * @param string $name The name of the variable.
     * @return mixed|null The value of the variable or null if not set.
     */
    public function get($name)
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Magic method to get a variable value.
     * 
     * @param string $name The name of the variable.
     * @return mixed|null The value of the variable or null if not set.
     */
    public function __get($name)
    {
        return $this->get($name);
    }
}
