<?php

namespace Flute\Core\Template\Concerns\TemplateCore;

use Illuminate\Support\ViewErrorBag;

trait HandlesGlobals
{
    public function addGlobal(string $name, $value): void
    {
        $this->globals[$name] = $value;
        $this->blade->share($name, $value);
    }

    public function addError(string $input, string $error): void
    {
        if (!isset($this->globals['errors'])) {
            $this->globals['errors'] = new ViewErrorBag();
        }

        $bag = $this->globals['errors']->getBag('default') ?? new \Illuminate\Support\MessageBag();
        $bag->add($input, $error);
        $this->globals['errors']->put('default', $bag);

        $this->blade->share('errors', $this->globals['errors']);
    }

    public function getGlobal(string $name)
    {
        return $this->globals[$name] ?? null;
    }
}
