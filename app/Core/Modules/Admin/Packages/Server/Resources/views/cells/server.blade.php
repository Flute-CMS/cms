<div class="flex flex-col">
    <div class="flex items-center gap-2">
        <span class="font-medium">{{ $server->name }}</span>
        @if ($server->display_ip)
            <span class="text-muted">({{ $server->display_ip }})</span>
        @else
            <span class="text-muted">({{ $server->getConnectionString() }})</span>
        @endif
    </div>
    <small class="text-muted">{{ $server->ranks }}</small>
</div>
