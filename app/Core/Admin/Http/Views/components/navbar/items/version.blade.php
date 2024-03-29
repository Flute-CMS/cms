@can('admin.system')
    <div class="header_version">
        <div class="version_text">
            <p>@t('admin.engine_version'):</p>
            <a href="#" data-tooltip="@t('admin.will_be_in_beta')" data-tooltip-conf="bottom multiline">@t('admin.check_updates')</a>
        </div>

        <div class="version" data-tooltip="@t('admin.installed_version')" data-tooltip-conf="bottom multiline">
            {{ app()->getVersion() }}
        </div>
    </div>
@endcan
