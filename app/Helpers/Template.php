<?php

use Flute\Core\Template\Template;

if (!function_exists("template")) {
    function template(): Template
    {
        static $instance = null;

        if ($instance === null) {
            $instance = app(Template::class);
        }

        return $instance;
    }
}

if (!function_exists("view")) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string|null  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        if (func_num_args() === 0) {
            return template()->getBlade();
        }

        return render($view, $data, $mergeData);
    }
}

if (!function_exists("render")) {
    function render(string $path, array $data = [], $mergeData = [])
    {
        return template()->render($path, $data, $mergeData);
    }
}