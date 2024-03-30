@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("Страница не найдена") : $message,
    "code" => 404,
])