@php
    $messageTypes = ['success', 'error', 'warning', 'info'];
@endphp

@if (!empty(flash()->peekAll()))
    <div class="container mt-3">
        <div class="row">
            <div class="col-12">
                @foreach ($messageTypes as $type)
                    @if (flash()->has($type))
                        @foreach (flash()->get($type) as $message)
                            <x-alert :type="$type">
                                {!! $message !!}
                            </x-alert>
                        @endforeach
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif
