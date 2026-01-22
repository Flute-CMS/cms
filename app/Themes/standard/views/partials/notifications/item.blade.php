<li class="notification-item {{ $notification->viewed ? 'viewed' : 'unread' }} {{ $notification->url ? 'hovered' : '' }}"
    data-id="{{ $notification->id }}"
    @if (!$notification->viewed) hx-put="{{ url('api/notifications/' . $notification->id) }}"
        hx-trigger="revealed once" hx-swap="none" hx-target="this" hx-boost="false" @endif
    @if ($notification->url && $notification->type !== 'button') onclick="location.href='{{ url($notification->url) }}'" @endif>
    <div class="notification-icon">
        @if ($notification->icon && (str_starts_with($notification->icon, 'http://') || str_starts_with($notification->icon, 'https://')))
            <img src="{{ url($notification->icon) }}" alt="{{ __($notification->title) }}" loading="lazy">
        @else
            <x-icon path="{{ $notification->icon ?? 'ph.regular.bell' }}" />
        @endif
    </div>
    <div class="notification-content">
        <div class="notification-header">
            <div class="notification-name">
                <h6>{{ __($notification->title) }}</h6>
                <small class="notification-date">{{ carbon($notification->createdAt)->diffForHumans() }}</small>
            </div>
            @if (!$notification->viewed)
                <span class="notification-unread-indicator"></span>
            @endif
        </div>

        @if (!empty($notification->content))
            <div class="notification-text">{!! markdown()->parse($notification->content) !!}</div>
        @endif

        @if ($notification->type === 'button' && !empty($notification->extra_data['buttons']))
            <div class="notification-buttons">
                @foreach ($notification->extra_data['buttons'] as $button)
                    <a href="{{ url($button['url'] ?? '#') }}"
                       class="notification-btn notification-btn--{{ $button['type'] ?? 'primary' }}"
                       @if (!empty($button['handler'])) data-notification-handler="{{ $button['handler'] }}" onclick="event.preventDefault(); event.stopPropagation();" @endif
                       @if (($button['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                        {{ __($button['label'] ?? $button['text'] ?? '') }}
                    </a>
                @endforeach
            </div>
        @elseif ($notification->type === 'file' && !empty($notification->url))
            <a href="{{ url($notification->url) }}" class="notification-file-link" target="_blank" onclick="event.stopPropagation();">
                <x-icon path="ph.regular.file-arrow-down" />
                @t('def.download_file')
            </a>
        @endif
    </div>

    <button class="notification-delete" data-notification-delete="{{ $notification->id }}"
        onclick="event.stopPropagation();" data-tooltip="@t('def.delete')">
        <x-icon path="ph.regular.trash" />
    </button>
</li>
