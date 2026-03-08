<x-alert type="warning" withClose=false>
    <div>
        <strong>@lang('admin-server.cron_warning.title')</strong>
        <p class="cron-warning__desc">@lang('admin-server.cron_warning.description')</p>

        <button type="button" class="btn size-sm btn-warning" data-a11y-dialog-show="cron-setup-modal">
            <x-icon path="ph.bold.gear-bold" />
            @lang('admin-server.cron_warning.setup_button')
        </button>
    </div>
</x-alert>

<x-modal id="cron-setup-modal" :title="__('admin-server.cron_warning.modal_title')">
    <div class="cron-setup">
        <p class="cron-setup__intro">@lang('admin-server.cron_warning.modal_description')</p>

        <h4 class="cron-setup__heading">Linux (crontab)</h4>
        <p class="cron-setup__label">@lang('admin-server.cron_warning.step_crontab')</p>
        <pre class="cron-setup__code">crontab -e</pre>

        <p class="cron-setup__label">@lang('admin-server.cron_warning.step_add_line')</p>
        <pre class="cron-setup__code">* * * * * cd {{ BASE_PATH }} && php flute cron:run >> /dev/null 2>&1</pre>

        <h4 class="cron-setup__heading">Windows (Task Scheduler)</h4>
        <p class="cron-setup__label">@lang('admin-server.cron_warning.step_windows')</p>
        <pre class="cron-setup__code">schtasks /create /sc minute /mo 1 /tn "FluteCron" /tr "php {{ BASE_PATH }}\flute cron:run"</pre>

        <h4 class="cron-setup__heading">@lang('admin-server.cron_warning.verify_title')</h4>
        <p class="cron-setup__label">@lang('admin-server.cron_warning.verify_description')</p>
        <pre class="cron-setup__code cron-setup__code--last">php flute cron:run</pre>
    </div>
</x-modal>
