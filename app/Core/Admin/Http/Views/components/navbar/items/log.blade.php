@can('admin.system')
    {{-- <div data-tooltip="@t('admin.create_log')" data-tooltip-conf="left">
        <a href="{{ url('/admin/api/createlog') }}" target="_blank" class="header_log">
            
        </a>
    </div> --}}

    <a href="{{ url('/admin/api/createlog') }}" target="_blank" class="icon-container report-generate">
        <span class="icon-text">@t('admin.create_log')</span>
        <i class="ph ph-file-plus"></i>
    </a>
@endcan
