@if (count($commands) > 0)
    <div class="search-commands__header">
        <span class="search-commands__title">{{ __('search.available_commands') }}</span>
    </div>
    <ul class="search-commands__list">
        @foreach ($commands as $command)
            <li class="search-command-item" data-command="{{ $command['command'] }}">
                <div class="search-command-item__icon">
                    @if (isset($command['icon']))
                        <x-icon :path="$command['icon']" />
                    @else
                        <x-icon path="ph.regular.terminal" />
                    @endif
                </div>
                <div class="search-command-item__content">
                    <span class="search-command-item__name">{{ $command['command'] }}</span>
                    <span class="search-tips__sep">—</span>
                    <span class="search-command-item__desc">{{ $command['description'] }}</span>
                </div>
                <x-icon path="ph.regular.arrow-right" class="search-command-item__arrow" />
            </li>
        @endforeach
    </ul>
@else
    <div class="search-no-results">
        <x-icon path="ph.regular.terminal" />
        <p>{{ __('search.no_commands') }}</p>
    </div>
@endif
