@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("def.not_allowed") : $message,
    "code" => 405,
])