<x-alert type="warning" withClose=false>
    <div>
        <strong>@lang('admin-dashboard.ioncube.perf_title')</strong>
        <div>@lang('admin-dashboard.ioncube.perf_desc')</div>

        <div>
            <div class="font-bold mt-2">@lang('admin-dashboard.ioncube.ini_title')</div>
            <div class="mb-1">@lang('admin-dashboard.ioncube.ini_note')</div>
            <pre><code>{{ $ini_line }}</code></pre>
        </div>
    </div>
</x-alert>
