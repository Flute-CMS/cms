@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("def.critical_error") : $message,
    "code" => 500,
])