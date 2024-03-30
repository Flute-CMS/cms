@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("Доступ запрещен") : $message,
    "code" => 403,
])