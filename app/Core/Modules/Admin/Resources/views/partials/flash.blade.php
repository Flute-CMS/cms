@php
    $messageTypes = ['success', 'error', 'warning', 'info'];
    $hasMessages = false;
    foreach ($messageTypes as $type) {
        if (flash()->has($type)) {
            $hasMessages = true;
            break;
        }
    }
@endphp

@if ($hasMessages)
    <div class="toast-stack">
        @foreach ($messageTypes as $type)
            @if (flash()->has($type))
                @foreach (flash()->get($type) as $message)
                    <div class="toast-item" data-type="{{ $type }}">
                        <x-alert :type="$type" :withClose="true" :onlyBorders="true">
                            {!! $message['text'] !!}
                            @if (isset($message['link']))
                                <a href="{{ $message['link']['href'] }}">{{ $message['link']['text'] }}</a>
                            @endif
                        </x-alert>
                    </div>
                @endforeach
            @endif
        @endforeach
    </div>

    <script>
        document.querySelectorAll('.toast-item').forEach(toast => {
            setTimeout(() => {
                toast.classList.add('hiding');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 5000);
        });
    </script>
@endif
