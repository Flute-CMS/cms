<div>
    <span class="badge {{ $server->enabled ? 'success' : 'warning' }}">
        {{ $server->enabled ? __('admin-server.status.active') : __('admin-server.status.inactive') }}
    </span>
</div>
