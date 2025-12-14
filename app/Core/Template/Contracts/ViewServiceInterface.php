<?php

namespace Flute\Core\Template\Contracts;

use Illuminate\View\View;

interface ViewServiceInterface
{
    public function render(string $template, array $context = [], $mergeData = []): View;

    /**
     * Add a global variable that will be available in all templates.
     *
     * @param string $name The name of the variable.
     * @param mixed $value The value of the variable.
     */
    public function addGlobal(string $name, $value): void;
}
