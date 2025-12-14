<div class="align-items-center navigation-item flex flex-row items-center gap-3">
    @if ($navbarItem->icon)
        <x-icon path="{{ $navbarItem->icon }}" class="navigation-item-icon" />
    @endif

    <div class="d-flex flex-column">
        <span class="d-flex align-center gap-3">
            {{ $navbarItem->title }}

            @if ($navbarItem->visibility === 'desktop')
                <span style="width: 26px; height: 26px; font-size: var(--p); padding: 0;" data-tooltip="Этот пункт отображается только на ПК" class="badge success">
                    <x-icon path="ph.regular.desktop" />
                </span>
            @elseif($navbarItem->visibility === 'mobile')
                <span style="width: 26px; height: 26px; font-size: var(--p); padding: 0;" data-tooltip="Этот пункт отображается только на мобильных устройствах" class="badge success">
                    <x-icon path="ph.regular.device-mobile" />
                </span>
            @endif
        </span>
        @if ($navbarItem->url)
            <a href="{{ url($navbarItem->url) }}" target="_blank"
                class="d-flex text-muted text-small hover-accent">{{ $navbarItem->url }}</a>
        @endif
    </div>
</div>
