<div class="align-items-center navigation-item flex flex-row items-center gap-3">
    @if ($footerItem->icon)
        <x-icon path="{{ $footerItem->icon }}" />
    @endif

    <div class="d-flex flex-column">
        <span>{{ $footerItem->title }}</span>
        @if ($footerItem->url)
            <a href="{{ url($footerItem->url) }}" target="_blank"
                class="d-flex text-muted text-small hover-accent">{{ $footerItem->url }}</a>
        @endif
    </div>
</div>
