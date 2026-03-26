<div class="sq {{ $status['online'] ? 'sq--on' : 'sq--off' }}">
    @if ($status['online'])
        <div class="sq__list">
            <div class="sq__item">
                <span class="sq__label">{{ __('admin-server.status.status') }}</span>
                <span class="sq__val"><span class="sq__dot"></span>{{ $status['players'] }}</span>
            </div>
            <div class="sq__item">
                <span class="sq__label">{{ __('admin-server.status.hostname') }}</span>
                <span class="sq__val" title="{{ $status['hostname'] }}">{{ \Illuminate\Support\Str::limit($status['hostname'], 32) }}</span>
            </div>
            <div class="sq__item">
                <span class="sq__label">{{ __('admin-server.status.map') }}</span>
                <span class="sq__val">{{ $status['map'] }}</span>
            </div>
            @if (isset($status['game']))
            <div class="sq__item">
                <span class="sq__label">{{ __('admin-server.status.game') }}</span>
                <span class="sq__val">{{ $status['game'] }}</span>
            </div>
            @endif
            @if (isset($status['vac']) && $status['vac'])
            <div class="sq__item">
                <span class="sq__label">VAC</span>
                <span class="sq__val sq__val--ok">{{ $status['vac'] }}</span>
            </div>
            @endif
        </div>

        @if (!empty($status['player_list']))
        <div class="sq__list sq__list--players">
            @foreach ($status['player_list'] as $name)
            <div class="sq__item sq__item--player">
                <span class="sq__avatar">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                <span class="sq__label">{{ $name }}</span>
            </div>
            @endforeach
            @if (!empty($status['player_list_truncated']))
            <div class="sq__item sq__item--more">
                <span class="sq__label">{{ __('admin-server.status.and_more', ['count' => $status['players_total'] ?? 0]) }}</span>
            </div>
            @endif
        </div>
        @endif
    @else
        <div class="sq__list">
            <div class="sq__item sq__item--err">
                <x-icon path="ph.bold.warning-bold" />
                <span class="sq__label">{{ $status['error'] ?? __('admin-server.messages.connection_failed') }}</span>
            </div>
        </div>
    @endif
</div>
