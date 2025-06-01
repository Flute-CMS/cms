@if (isset($js))
    @push('scripts')
        @foreach ($js as $jsFile)
            @at($jsFile)
        @endforeach
    @endpush
@endif

@if (isset($css))
    @push('styles')
        @foreach ($css as $cssFile)
            @at($cssFile)
        @endforeach
    @endpush
@endif

<!-- ID need for correct htmx swap -->
<div id="screen-container">
    @section('title', (string) __($screenName ?? ''))
    @section('description', (string) __($screenDescription ?? ''))

    @if ($screenName || $screenDescription || ! empty($screenCommandBar))
        <legend class="base-legend">
            <div>
                <h4>{{ __($screenName ?? '') }} @if ($screenPopover)
                    <x-popover :content="$screenPopover" />
                @endif
                </h4>

                @if (! empty($screenDescription))
                    <small class="d-block text-muted text-balance mb-0">
                        {!! __($screenDescription ?? '') !!}
                    </small>
                @endif
            </div>
            <ul class="row g-2 p-0">
                @foreach ($screenCommandBar as $command)
                    <li class="col">
                        {!! $command !!}
                    </li>
                @endforeach
            </ul>
        </legend>
    @endif

    @includeWhen(request()->isBoost(), 'admin::partials.breadcrumb')

    {!! $screenLayouts ?? '' !!}

    <div id="modals-container" @if (user()->device()->isMobile()) hx-swap="outerHTML" @endif>
        @stack('modals-container')
    </div>
</div>