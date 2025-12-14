@if (count($commands) > 0)
    <div class="search-results-count">
        <span>{{ __('search.available_commands') }}:</span>
    </div>
    <ul class="command-suggestions-list">
        @foreach ($commands as $command)
            <li class="command-suggestion-item" data-command="{{ $command['command'] }}" data-tooltip="{{ $command['description'] }}" role="option" aria-selected="false">
                @if (isset($command['icon']))
                    <x-icon :path="$command['icon']" class="command-icon" />
                @endif
                <div class="command-name">{{ $command['command'] }}</div>
            </li>
        @endforeach
    </ul>
@else
    <p class="text-muted">{{ __('search.no_commands_available') }}</p>
@endif 