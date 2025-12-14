@php
    $messageTypes = ['success', 'error', 'warning', 'info'];
@endphp

@foreach ($messageTypes as $type)
    @if (flash()->has($type))
        @foreach (flash()->get($type) as $message)
            <x-alert :type="$type">
                {!! $message['text'] !!}
                @if (isset($message['link']))
                    <a href="{{ $message['link']['href'] }}">{{ $message['link']['text'] }}</a>
                @endif
            </x-alert>
        @endforeach
    @endif
@endforeach
