@php
    $channels = $model->getChannels() ?: ['inapp'];
    $channelIcons = [
        'inapp' => 'ph.bold.bell-bold',
        'email' => 'ph.bold.envelope-bold',
        'telegram' => 'ph.bold.telegram-logo-bold',
        'push' => 'ph.bold.device-mobile-bold',
    ];
@endphp

<div class="notification-channels">
    @foreach ($channels as $channel)
        <span class="notification-channels__item notification-channels__item--{{ $channel }}"
            data-tooltip="{{ __('admin-notifications.channels.' . $channel) }}"
            data-tooltip-pos="top">
            <x-icon :path="$channelIcons[$channel] ?? 'ph.bold.broadcast-bold'" />
        </span>
    @endforeach
</div>
