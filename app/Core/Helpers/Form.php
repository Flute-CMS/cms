<?php

use Flute\Core\Services\FormService;

if (!function_exists("form")) {
    function form(array $defaults = []): FormService
    {
        /** @var FormService $formService */
        $formService = app(FormService::class);

        $formService->setDefaults($defaults);

        return $formService;
    }
}