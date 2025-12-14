<li class="notification-item {{ $notification->viewed ? 'viewed' : 'unread' }} {{ $notification->url ? 'hovered' : '' }}"
    data-id="{{ $notification->id }}"
    @if (!$notification->viewed) hx-put="{{ url('api/notifications/' . $notification->id) }}"
            hx-trigger="revealed once"
            hx-swap="none" @endif
    @if ($notification->url) onclick="location.href='{{ url($notification->url) }}'" @endif>
    <div class="notification-icon">
        @if (str_starts_with($notification->icon, 'http://') || str_starts_with($notification->icon, 'https://'))
            <img src="{{ url($notification->icon) }}" alt="{{ __($notification->title) }}" loading="lazy">
        @else
            <x-icon path="{{ $notification->icon ?? 'ph.regular.bell' }}" class="icon" />
        @endif
    </div>
    <div class="notification-content">
        <div class="notification-header">
            <div class="notification-name">
                <h6>{{ __(key: $notification->title) }}</h6>
                <small class="notification-date">{{ carbon($notification->createdAt)->diffForHumans() }}</small>
            </div>

            @if (!$notification->viewed)
                <span class="notification-unread-indicator"></span>
            @endif
        </div>

        {{-- FOR NOW, SUPPORTS ONLY TEXT --}}
        @if ($notification->type == 'text')
            @if (!empty($notification->content))
                <div class="notification-text md-content">
                    {!! markdown()->parse($notification->content) !!}
                </div>
            @endif
        @endif
    </div>
</li>
