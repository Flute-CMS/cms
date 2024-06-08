@extends(tt('errors/layout.blade.php'), [
    "message" => empty($message) ? __("def.maintenance_mode") : $message,
    "code" => 503,
    "withoutButton" => true,
    "withAuthBtn" => true
])