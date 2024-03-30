@push('header')
    @at(tt('assets/styles/components/_flash.scss'))
@endpush

@if(flash()->has('success'))
    @foreach (flash()->get('success') as $message)
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <circle cx="12" cy="12" r="9" />
                <path d="M9 12l2 2l4 -4" />
            </svg>
            @if(is_array($message))
                {!! $message['text'] !!}
                @if(isset($message['link']))
                    <a href="{{ $message['link']['href'] }}">{{ $message['link']['text'] }}</a>
                @endif
            @else
                <div>
                    {!! $message !!}
                </div>
            @endif
        </div>
    @endforeach
@endif

@if(flash()->has('error'))
    @foreach (flash()->get('error') as $message)
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <circle cx="12" cy="12" r="9" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            @if(is_array($message))
                {!! $message['text'] !!}
                @if(isset($message['link']))
                    <a href="{{ $message['link']['href'] }}">{{ $message['link']['text'] }}</a>
                @endif
            @else
                <div>
                    {!! $message !!}
                </div>
            @endif
        </div>
    @endforeach
@endif

@if(flash()->has('warning'))
    @foreach (flash()->get('warning') as $message)
        <div class="alert alert-warning">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <circle cx="12" cy="12" r="9" />
                <line x1="12" y1="10" x2="12" y2="14" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            @if(is_array($message))
                {!! $message['text'] !!}
                @if(isset($message['link']))
                    <a href="{{ $message['link']['href'] }}">{{ $message['link']['text'] }}</a>
                @endif
            @else
                <div>
                    {!! $message !!}
                </div>
            @endif
        </div>
    @endforeach
@endif

@if(flash()->has('info'))
    @foreach (flash()->get('info') as $message)
        <div class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <circle cx="12" cy="12" r="9" />
                <line x1="12" y1="16" x2="12" y2="12" />
                <line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
            @if(is_array($message))
                {!! $message['text'] !!}
                @if(isset($message['link']))
                    <a href="{{ $message['link']['href'] }}">{{ $message['link']['text'] }}</a>
                @endif
            @else
                <div>
                    {!! $message !!}
                </div>
            @endif
        </div>
    @endforeach
@endif
