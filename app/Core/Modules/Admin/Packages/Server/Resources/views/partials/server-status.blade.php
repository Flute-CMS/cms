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
            <x-icon name="ph.bold.warning-bold" />
            <span>{{ $status['error'] ?? __('admin-server.messages.connection_failed') }}</span>
        </div>
    @endif
</div>

<style>
    .server-status-card {
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
    }

    .server-status-online {
        background: rgba(var(--success-rgb), 0.1);
        border-color: rgba(var(--success-rgb), 0.3);
    }

    .server-status-offline {
        background: rgba(var(--danger-rgb), 0.1);
        border-color: rgba(var(--danger-rgb), 0.3);
    }

    .server-status-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    .server-status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .server-status-indicator.online {
        background-color: var(--success);
        box-shadow: 0 0 8px var(--success);
    }

    .server-status-indicator.offline {
        background-color: var(--danger);
    }

    .server-status-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .server-status-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.875rem;
    }

    .server-status-key {
        color: var(--text-secondary);
    }

    .server-status-value {
        font-weight: 500;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 60%;
    }

    .server-status-error {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--danger);
        font-size: 0.875rem;
    }

    .server-status-error span {
        word-break: break-word;
    }
</style>
