@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("Страница не обслуживается") : $message,
    "code" => 405,
])