<div class="d-flex flex-wrap gap-1">
    @forelse ($server->dbconnections as $conn)
        <span class="badge primary">{{ $conn->mod }}</span>
    @empty
        <span class="text-muted" style="font-size: 12px">—</span>
    @endforelse
</div>
