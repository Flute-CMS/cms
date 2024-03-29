<?php

namespace Flute\Core\Contracts;

interface ViewServiceInterface
{
    /**
     * Render the given template with the given variables.
     *
     * @param string $template The name of the template.
     * @param array $context The variables to pass to the template.
     * @param bool $templatePath Whether to use the template path or not.
     * @return string The rendered template.
     */
    public function render(string $template, array $context = [], bool $templatePath = true): string;

    /**
     * Extend a template with another template.
     *
     * @param string $template The name of the template to extend.
     * @param string $extension The name of the template to use as the extension.
     * @return void
     */
    public function extendTemplate(string $template, string $extension): void;

    /**
     * Add a global variable that will be available in all templates.
     *
     * @param string $name The name of the variable.
     * @param mixed $value The value of the variable.
     * @return void
     */
    public function addGlobal(string $name, $value): void;

    /**
     * Add a custom function to Blade.
     *
     * @param string $name The name of the function.
     * @param callable $function The function to add.
     * @return void
     */
    public function addFunction(string $name, callable $function): void;
}
