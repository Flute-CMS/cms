<?php

use Flute\Core\Support\FluteRequest;
use Flute\Core\Support\RedirectResponse;
use Flute\Core\Support\Response as SupportResponse;
use Flute\Core\Template\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

if (!function_exists("view")) {
    function view(string $path, array $data = [], bool $useTheme = false, int $status = 200, array $headers = []): Response
    {
        return response()->make(
            render($path, $data, $useTheme),
            $status,
            $headers
        );
    }
}

if (!function_exists("render")) {
    function render(string $path, array $data = [], bool $useTheme = false): string
    {
        /** @var Template $view */
        $view = app(Template::class);
        
        return $view->render($path, $data, $useTheme);
    }
}

if (!function_exists("response")) {
    function response(): SupportResponse
    {
        return app(SupportResponse::class);
    }
}

if (!function_exists("request")) {
    function request(): FluteRequest
    {
        return app(FluteRequest::class);
    }
}

if (!function_exists("json")) {
    function json($data, int $status = 200, array $headers = [], bool $json = false): JsonResponse
    {
        return response()->json($data, $status, $headers, $json);
    }
}

if (!function_exists("redirect")) {
    function redirect(?string $to = null, int $status = 302, array $headers = []): RedirectResponse
    {
        return app()->make(RedirectResponse::class, ['to' => $to, 'status' => $status, 'headers' => $headers]);
    }
}