@if ($notifications)
    <ul class="notifications__items">
        @foreach ($notifications as $notification)
            @include('flute::partials.notifications.item', ['notification' => $notification])
        @endforeach
    </ul>
@else
    <p class="notifications__empty">@t('def.no_notifications')</p>
@endif
