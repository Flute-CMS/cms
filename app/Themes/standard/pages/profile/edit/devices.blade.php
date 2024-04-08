@push('header')
    @at(tt('assets/styles/pages/profile_edit/devices.scss'))
    <link rel="stylesheet" href="@asset('assets/css/libs/tables.css')" />
@endpush

@push('profile_edit_content')
    <div class="card">
        <div class="card-header">
            @t('profile.devices.info')
        </div>
        <div class="profile_settings">
            <div class="overflow-table">
                <table class="dataTable table-devices table">
                    <thead>
                        <tr>
                            <th>@t('profile.devices.type')</th>
                            <th class="text-center">IP</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr role="row">
                                <td class="device-details">
                                    @if ($device['is_mobile'])
                                        <i class="ph ph-device-mobile-speaker"></i>
                                    @else
                                        <i class="ph ph-monitor"></i>
                                    @endif
                                    <p>{{ $device['platform'] }}, {{ $device['browser'] }}</p>
                                </td>
                                <td>{{ $device['ip'] }}</td>
                                <td class="text-center">
                                    <button class="device-endsession" data-id="{{ $device['id'] }}">@t('profile.devices.end')</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endpush

@push('footer')
    @at(tt('assets/js/pages/profile_edit/devices.js'))
@endpush
