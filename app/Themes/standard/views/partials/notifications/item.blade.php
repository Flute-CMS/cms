<li class="notification-item {{ $notification->viewed ? 'viewed' : 'unread' }} {{ $notification->url ? 'hovered' : '' }}"
    data-id="{{ $notification->id }}"
    @if ($notification->url && $notification->type !== 'button') data-url="{{ url($notification->url) }}" @endif>
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

        @php $extraData = $notification->getExtraData(); @endphp
        @if ($notification->type === 'button' && !empty($extraData['buttons']))
            <div class="notification-buttons">
                @foreach ($extraData['buttons'] as $button)
                    @php
                        $btnStyle = $button['style'] ?? $button['type'] ?? 'primary';
                        $btnAction = $button['action'] ?? 'navigate';
                        $btnLabel = __($button['label'] ?? $button['text'] ?? '');
                        $btnUrl = url($button['url'] ?? '#');
                    @endphp
                    @if ($btnAction === 'api')
                        @php $btnMethod = strtolower($button['method'] ?? 'post'); @endphp
                        <button type="button"
                            class="notification-btn notification-btn--{{ $btnStyle }}"
                            @if ($btnMethod === 'delete') hx-delete="{{ $btnUrl }}"
                            @elseif ($btnMethod === 'put') hx-put="{{ $btnUrl }}"
                            @else hx-post="{{ $btnUrl }}"
                            @endif
                            hx-swap="none"
                            hx-on:htmx:after-request="if(event.detail.successful){this.closest('.notification-item').style.height=this.closest('.notification-item').offsetHeight+'px';this.closest('.notification-item').style.transition='opacity 0.25s,height 0.25s 0.1s';this.closest('.notification-item').style.opacity='0';this.closest('.notification-item').style.height='0';this.closest('.notification-item').style.overflow='hidden';setTimeout(()=>this.closest('.notification-item')?.remove(),400);}">
                            {{ $btnLabel }}
                        </button>
                    @elseif ($btnAction === 'dismiss')
                        <button type="button"
                            class="notification-btn notification-btn--{{ $btnStyle }}"
                            onclick="this.closest('.notification-item').style.opacity='0';setTimeout(()=>this.closest('.notification-item')?.remove(),250)">
                            {{ $btnLabel }}
                        </button>
                    @else
                        <a href="{{ $btnUrl }}"
                            class="notification-btn notification-btn--{{ $btnStyle }}"
                            @if (($button['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                            {{ $btnLabel }}
                        </a>
                    @endif
                @endforeach
            </div>
        @elseif ($notification->type === 'file' && !empty($notification->url))
            <a href="{{ url($notification->url) }}" class="notification-file-link" target="_blank" rel="noopener">
                <x-icon path="ph.regular.file-arrow-down" />
                @t('def.download_file')
            </a>
        @endif
    </div>

    <button class="notification-delete" data-notification-delete="{{ $notification->id }}"
        data-tooltip="@t('def.delete')">
        <x-icon path="ph.regular.trash" />
    </button>
</li>
