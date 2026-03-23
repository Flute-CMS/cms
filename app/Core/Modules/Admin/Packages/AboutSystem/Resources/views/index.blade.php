<div class="about-sys">
    {{-- Report download --}}
    <div class="about-sys__report">
        <div class="about-sys__report-left">
            <x-icon path="ph.regular.file-text" class="about-sys__report-icon" />
            <div>
                <div class="about-sys__report-title">{{ __('admin-about-system.report.title') }}</div>
                <div class="about-sys__report-desc">{{ __('admin-about-system.report.description') }}</div>
            </div>
        </div>
        <a href="{{ url('/admin/about-system/download-report') }}" class="about-sys__report-btn" data-turbo="false" hx-boost="false">
            <x-icon path="ph.bold.download-simple-bold" />
            {{ __('admin-about-system.report.download') }}
        </a>
    </div>

    {{-- Performance --}}
    @if ($performanceData['hasData'])
        @push('head')
            <script src="{{ asset('assets/js/libs/apex-charts.js') }}" defer></script>
        @endpush

        <div class="about-sys__section">
            <div class="about-sys__section-header">
                <x-icon path="ph.bold.gauge-bold" />
                <span>{{ __('admin-about-system.sections.performance.title') }}</span>
            </div>

            <div class="about-sys__stats">
                <div class="about-sys__stat">
                    <span class="about-sys__stat-value">{{ $performanceData['overview']['total_requests'] }}</span>
                    <span class="about-sys__stat-label">{{ __('admin-about-system.charts.total_requests') }}</span>
                </div>
                <div class="about-sys__stat">
                    <span class="about-sys__stat-value">{{ $performanceData['overview']['avg_response_time'] }}<small>ms</small></span>
                    <span class="about-sys__stat-label">{{ __('admin-about-system.charts.avg_response') }}</span>
                </div>
                <div class="about-sys__stat">
                    <span class="about-sys__stat-value">{{ $performanceData['overview']['avg_db_time'] }}<small>ms</small></span>
                    <span class="about-sys__stat-label">{{ __('admin-about-system.charts.avg_db_time') }}</span>
                </div>
                <div class="about-sys__stat">
                    <span class="about-sys__stat-value">{{ $performanceData['overview']['avg_memory'] }}<small>MB</small></span>
                    <span class="about-sys__stat-label">{{ __('admin-about-system.charts.avg_memory') }}</span>
                </div>
                <div class="about-sys__stat">
                    <span class="about-sys__stat-value">{{ $performanceData['overview']['routes_count'] }}</span>
                    <span class="about-sys__stat-label">{{ __('admin-about-system.charts.routes_tracked') }}</span>
                </div>
                <div class="about-sys__stat">
                    <span class="about-sys__stat-value">{{ $performanceData['overview']['widgets_count'] }}</span>
                    <span class="about-sys__stat-label">{{ __('admin-about-system.charts.widgets_tracked') }}</span>
                </div>
            </div>

            @if ($performanceData['overview']['last_updated'])
                <div class="about-sys__updated">
                    {{ __('admin-about-system.charts.last_updated') }}: {{ $performanceData['overview']['last_updated'] }}
                </div>
            @endif
        </div>

        @php
            $charts = array_filter([
                ['chart' => $routesChart, 'icon' => 'ph.regular.path', 'title' => __('admin-about-system.charts.slowest_routes')],
                ['chart' => $queriesChart, 'icon' => 'ph.regular.database', 'title' => __('admin-about-system.charts.slowest_queries')],
                ['chart' => $widgetsChart, 'icon' => 'ph.regular.squares-four', 'title' => __('admin-about-system.charts.slowest_widgets')],
                ['chart' => $modulesChart, 'icon' => 'ph.regular.package', 'title' => __('admin-about-system.charts.slowest_modules')],
                ['chart' => $providersChart, 'icon' => 'ph.regular.plugs-connected', 'title' => __('admin-about-system.charts.slowest_providers')],
            ], fn($item) => $item['chart'] !== null);
        @endphp

        @if (count($charts) > 0)
            <div class="about-sys__charts-grid">
                @foreach ($charts as $chartData)
                    <div class="about-sys__section">
                        <div class="about-sys__section-header">
                            <x-icon path="{{ $chartData['icon'] }}" />
                            <span>{{ $chartData['title'] }}</span>
                        </div>
                        <div class="about-sys__chart">
                            {!! $chartData['chart']->container() !!}
                        </div>
                        {!! $chartData['chart']->script() !!}
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="about-sys__section">
            <div class="about-sys__section-header">
                <x-icon path="ph.bold.gauge-bold" />
                <span>{{ __('admin-about-system.sections.performance.title') }}</span>
            </div>
            <div class="about-sys__empty">
                <x-icon path="ph.regular.chart-line" />
                <p>{{ __('admin-about-system.charts.no_data') }}</p>
                <small>{{ __('admin-about-system.charts.no_data_hint') }}</small>
            </div>
        </div>
    @endif

    {{-- Info + PHP --}}
    <div class="row gx-3 gy-3">
        <div class="col-md-6">
            <div class="about-sys__group">
                <div class="about-sys__group-header">
                    <x-icon path="ph.bold.info-bold" />
                    <span>{{ __('admin-about-system.sections.system_info.title') }}</span>
                </div>
                <div class="about-sys__group-body">
                    <div class="about-sys__kv">
                        <span class="about-sys__kv-label">{{ __('admin-about-system.labels.author') }}</span>
                        <a href="https://github.com/FlamesONE" target="_blank" rel="noopener" class="about-sys__kv-link">
                            {{ explode(' <', $systemInfo['author'])[0] ?? $systemInfo['author'] }}
                            <x-icon path="ph.bold.arrow-square-out-bold" />
                        </a>
                    </div>
                    <div class="about-sys__kv">
                        <span class="about-sys__kv-label">{{ __('admin-about-system.labels.project_link') }}</span>
                        <a href="{{ $systemInfo['project_link'] }}" target="_blank" rel="noopener" class="about-sys__kv-link">
                            GitHub
                            <x-icon path="ph.bold.arrow-square-out-bold" />
                        </a>
                    </div>
                    <div class="about-sys__kv">
                        <span class="about-sys__kv-label">{{ __('admin-about-system.labels.license') }}</span>
                        <span class="about-sys__badge about-sys__badge--accent">{{ $systemInfo['license'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="about-sys__group">
                <div class="about-sys__group-header">
                    <x-icon path="ph.bold.code-bold" />
                    <span>{{ __('admin-about-system.sections.php_info.title') }}</span>
                </div>
                <div class="about-sys__group-body">
                    @foreach (['version', 'memory_limit', 'max_execution_time', 'upload_max_filesize', 'post_max_size', 'opcache'] as $key)
                        @if (isset($phpInfo[$key]))
                            @php
                                $hasWarning = isset($phpWarnings[$key]);
                                $warningMessage = $hasWarning ? $phpWarnings[$key] : '';
                            @endphp
                            <div class="about-sys__kv">
                                <span class="about-sys__kv-label">
                                    {{ __('admin-about-system.labels.' . $key) }}
                                    @if ($hasWarning)
                                        <x-icon path="ph.bold.warning-bold" data-tooltip="{{ $warningMessage }}" />
                                    @endif
                                </span>
                                @if ($key === 'version')
                                    <span class="about-sys__badge {{ $phpVersionValid ? 'about-sys__badge--ok' : 'about-sys__badge--warn' }}">{{ $phpInfo[$key] }}</span>
                                @elseif ($key === 'opcache' || $key === 'jit')
                                    <span class="about-sys__badge {{ $phpInfo[$key] === 'Enabled' ? 'about-sys__badge--ok' : 'about-sys__badge--warn' }}">{{ $phpInfo[$key] }}</span>
                                @else
                                    <span class="about-sys__kv-value">{{ $phpInfo[$key] }}</span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Server + Health --}}
    <div class="row gx-3 gy-3 mt-0">
        <div class="col-md-6">
            <div class="about-sys__group">
                <div class="about-sys__group-header">
                    <x-icon path="ph.bold.hard-drives-bold" />
                    <span>{{ __('admin-about-system.sections.server_info.title') }}</span>
                </div>
                <div class="about-sys__group-body">
                    @foreach ($serverInfo as $key => $value)
                        <div class="about-sys__kv">
                            <span class="about-sys__kv-label">{{ __('admin-about-system.labels.' . $key) }}</span>
                            <span class="about-sys__kv-value">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="about-sys__group">
                <div class="about-sys__group-header">
                    <x-icon path="ph.bold.heartbeat-bold" />
                    <span>{{ __('admin-about-system.sections.system_health.title') }}</span>
                </div>
                <div class="about-sys__group-body about-sys__group-body--health">
                    {{-- Memory --}}
                    @php
                        $memoryUsage = memory_get_usage(true);
                        $memoryLimit = ini_get('memory_limit');
                        $memoryLimitBytes = preg_replace('/[^0-9]/', '', $memoryLimit) * 1024 * 1024;
                        $memoryPct = $memoryLimitBytes > 0 ? min(100, round(($memoryUsage / $memoryLimitBytes) * 100)) : 0;
                        $memClass = $memoryPct > 85 ? '--error' : ($memoryPct > 70 ? '--warn' : '--ok');
                    @endphp
                    <div class="about-sys__meter">
                        <div class="about-sys__meter-header">
                            <span>{{ __('admin-about-system.labels.memory_usage') }}</span>
                            <span class="about-sys__meter-val">{{ round($memoryUsage / 1024 / 1024, 1) }}MB / {{ $memoryLimit }}</span>
                        </div>
                        <div class="about-sys__meter-track">
                            <div class="about-sys__meter-fill about-sys__meter-fill{{ $memClass }}" style="width: {{ $memoryPct }}%"></div>
                        </div>
                    </div>

                    {{-- Disk --}}
                    @php
                        $diskTotal = disk_total_space(__DIR__);
                        $diskFree = disk_free_space(__DIR__);
                        $diskUsed = $diskTotal - $diskFree;
                        $diskPct = round(($diskUsed / $diskTotal) * 100);
                        $diskClass = $diskPct > 85 ? '--error' : ($diskPct > 70 ? '--warn' : '--ok');
                    @endphp
                    <div class="about-sys__meter">
                        <div class="about-sys__meter-header">
                            <span>{{ __('admin-about-system.labels.disk_usage') }}</span>
                            <span class="about-sys__meter-val">{{ round($diskUsed / 1024 / 1024 / 1024, 1) }}GB / {{ round($diskTotal / 1024 / 1024 / 1024, 1) }}GB</span>
                        </div>
                        <div class="about-sys__meter-track">
                            <div class="about-sys__meter-fill about-sys__meter-fill{{ $diskClass }}" style="width: {{ $diskPct }}%"></div>
                        </div>
                    </div>

                    {{-- CPU --}}
                    <div class="about-sys__kv" style="border: 0; padding-bottom: 0;">
                        <span class="about-sys__kv-label">{{ __('admin-about-system.labels.cpu_load') }}</span>
                        <span class="about-sys__kv-value">
                            {{ $resourceUsage['cpu_load']['1min'] }}
                            <small>/ {{ $resourceUsage['cpu_load']['5min'] }}</small>
                            <small>/ {{ $resourceUsage['cpu_load']['15min'] }}</small>
                        </span>
                    </div>

                    {{-- RAM --}}
                    @php
                        $ramPct = $resourceUsage['ram']['percent'];
                        $ramClass = $ramPct > 85 ? '--error' : ($ramPct > 70 ? '--warn' : '--ok');
                    @endphp
                    <div class="about-sys__meter">
                        <div class="about-sys__meter-header">
                            <span>{{ __('admin-about-system.labels.ram_usage') }}</span>
                            <span class="about-sys__meter-val">
                                {{ \Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper::formatBytes($resourceUsage['ram']['used']) }}
                                / {{ \Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper::formatBytes($resourceUsage['ram']['total']) }}
                                ({{ $ramPct }}%)
                            </span>
                        </div>
                        <div class="about-sys__meter-track">
                            <div class="about-sys__meter-fill about-sys__meter-fill{{ $ramClass }}" style="width: {{ $ramPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PHP Extensions --}}
    <div class="about-sys__group about-sys__group--wide">
        <div class="about-sys__group-header">
            <x-icon path="ph.bold.puzzle-piece-bold" />
            <span>{{ __('admin-about-system.sections.requirements.title') }}</span>
        </div>
        <div class="about-sys__extensions">
            @foreach ($requiredExtensions as $extension => $info)
                <div class="about-sys__ext {{ $info['loaded'] ? 'about-sys__ext--ok' : ($info['required'] ? 'about-sys__ext--error' : 'about-sys__ext--warn') }}">
                    <x-icon path="{{ $info['loaded'] ? 'ph.bold.check-circle-bold' : 'ph.bold.x-circle-bold' }}" class="about-sys__ext-icon" />
                    <div class="about-sys__ext-info">
                        <span class="about-sys__ext-name">
                            {{ $extension }}
                            @if ($info['required'])
                                <span class="about-sys__ext-req" data-tooltip="{{ __('admin-about-system.requirements.required_extension') }}">*</span>
                            @endif
                        </span>
                        <span class="about-sys__ext-desc">{{ $info['description'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
