@props([
    'user',
    'color' => true,
    'link' => false,
    'tag' => 'span',
])

{!! $user->getDisplayName($attributes->get('class', ''), $color, $link) !!}
