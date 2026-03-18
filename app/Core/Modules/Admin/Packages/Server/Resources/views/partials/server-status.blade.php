<div class="server-status-card {{ $status['online'] ? 'server-status-online' : 'server-status-offline' }}">
    <div class="server-status-header">
        <div class="server-status-indicator {{ $status['online'] ? 'online' : 'offline' }}"></div>
        <span class="server-status-label">
            {{ $status['online'] ? __('admin-server.status.online') : __('admin-server.status.offline') }}
        </span>
    </div>

    @if($status['online'])
        <div class="server-status-info">
            <div class="server-status-row">
                <span class="server-status-key">{{ __('admin-server.status.hostname') }}:</span>
                <span class="server-status-value" title="{{ $status['hostname'] }}">{{ \Illuminate\Support\Str::limit($status['hostname'], 30) }}</span>
            </div>
            <div class="server-status-row">
                <span class="server-status-key">{{ __('admin-server.status.map') }}:</span>
                <span class="server-status-value">{{ $status['map'] }}</span>
            </div>
            <div class="server-status-row">
                <span class="server-status-key">{{ __('admin-server.status.players') }}:</span>
                <span class="server-status-value">{{ $status['players'] }}</span>
            </div>
            @if(isset($status['game']))
            <div class="server-status-row">
                <span class="server-status-key">{{ __('admin-server.status.game') }}:</span>
                <span class="server-status-value">{{ $status['game'] }}</span>
            </div>
            @endif
            @if(isset($status['vac']))
            <div class="server-status-row">
                <span class="server-status-key">VAC:</span>
                <span class="server-status-value">{{ $status['vac'] }}</span>
            </div>
            @endif
        </div>
    @else
        <div class="server-status-error">
            <x-icon path="ph.bold.warning-bold" />
            <span>{{ $status['error'] ?? __('admin-server.messages.connection_failed') }}</span>
        </div>
    @endif
</div>

