@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("Сайт упал. Смотрите логи.") : $message,
    "code" => 500,
])