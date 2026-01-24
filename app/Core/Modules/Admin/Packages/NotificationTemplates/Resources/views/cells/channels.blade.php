@php
    $channels = $model->getChannels() ?: ['inapp'];
    $channelIcons = [
        'inapp' => 'ph.bell',
        'email' => 'ph.envelope',
        'telegram' => 'ph.telegram-logo',
        'push' => 'ph.device-mobile',
    ];
@endphp

<div class="d-flex flex-wrap gap-1">
    @foreach ($channels as $channel)
        <span class="badge outline-primary d-inline-flex align-items-center gap-1">
            <x-icon :name="$channelIcons[$channel] ?? 'ph.broadcast'" size="14" />
            {{ $channel }}
        </span>
    @endforeach
</div>
