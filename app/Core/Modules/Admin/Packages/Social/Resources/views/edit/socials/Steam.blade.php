@if (config('app.steam_api'))
    <x-alert type="success" :withClose="false" class="mb-0">
        {{ __('admin-social.edit.steam_success') }}
    </x-alert>
@else
    <x-alert type="warning" :withClose="false" class="mb-0">
        {!! __('admin-social.edit.steam_error') !!}
    </x-alert>
@endif
