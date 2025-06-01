@php
    $colors = app('flute.view.manager')->getColors();
@endphp

@if (!empty($colors))
    <style>
        @isset($colors['dark'])
            :root[data-theme="dark"] {
                @foreach ($colors['dark'] as $key => $value)
                    {{ $key }}: {{ $value }};
                @endforeach
            }
        @endisset

        @isset($colors['light'])
            :root[data-theme="light"] {
                @foreach ($colors['light'] as $key => $value)
                    {{ $key }}: {{ $value }};
                @endforeach
            }
        @endisset
    </style>
@endif
