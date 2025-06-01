<div class="page-info">
    <div class="page-info-title">{{ $page->title }}</div>
    <div class="page-info-route">{{ $page->route }}</div>
    @if ($page->description)
        <div class="page-info-description">{{ \Illuminate\Support\Str::limit($page->description, 100) }}</div>
    @endif
</div>
