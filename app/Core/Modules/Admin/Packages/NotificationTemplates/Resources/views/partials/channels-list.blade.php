@props([
    'channels' => [],
    'enabledChannels' => ['inapp'],
])

<div class="channels-list">
    @foreach ($channels as $key => $channel)
        @php
            $isChecked = in_array($key, $enabledChannels, true);
            $isDisabled = !$channel['enabled'];
        @endphp
        <div class="channels-list__item {{ $isDisabled ? 'channels-list__item--disabled' : '' }}">
            <div class="channels-list__info">
                <div class="channels-list__icon">
                    <x-icon :path="$channel['icon']" />
                </div>
                <span class="channels-list__name">{{ $channel['name'] }}</span>
                @if ($isDisabled)
                    <span class="channels-list__badge channels-list__badge--unavailable">
                        {{ __('admin-notifications.channels_status.unavailable') }}
                    </span>
                @endif
            </div>
            @include('admin::components.fields.toggle', [
                'name' => 'channels_' . $key,
                'id' => 'channel_' . $key,
                'checked' => $isChecked,
                'disabled' => $isDisabled,
            ])
        </div>
    @endforeach
</div>
