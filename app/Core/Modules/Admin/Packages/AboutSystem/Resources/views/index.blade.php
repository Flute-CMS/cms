<div class="about-system">
    <div class="about-system__support-banner">
        <h2><x-icon path="ph.bold.music-notes-bold" /> {{ __('admin-about-system.labels.donate_title') }}</h2>
        <p>{{ __('admin-about-system.labels.donate_description') }}</p>
        <div class="about-system__support-actions">
            <a href="https://github.com/Flute-CMS/cms/sponsors" target="_blank"
                class="about-system__btn about-system__btn--primary">
                <x-icon path="ph.bold.heart-bold" />
                {{ __('admin-about-system.support.github_sponsors') }}
            </a>
            <a href="https://github.com/Flute-CMS/cms" target="_blank" class="about-system__btn">
                <x-icon path="ph.regular.github-logo" />
                {{ __('admin-about-system.support.github') }}
            </a>
        </div>
    </div>

    <div class="about-system__report-section">
        <div class="about-system__report-content">
            <div class="about-system__report-info">
                <h3><x-icon path="ph.regular.file-text" /> {{ __('admin-about-system.report.title') }}</h3>
                <p>{{ __('admin-about-system.report.description') }}</p>
            </div>
            <a href="{{ url('/admin/about-system/download-report') }}" class="about-system__report-btn" data-turbo="false">
                <x-icon path="ph.bold.download-simple-bold" />
                {{ __('admin-about-system.report.download') }}
            </a>
        </div>
    </div>

    @if ($performanceData['hasData'])
        @push('head')
            <script src="{{ asset('assets/js/libs/apex-charts.js') }}"></script>
        @endpush

        <section class="about-system__panel about-system__panel--full">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.gauge" />
                {{ __('admin-about-system.sections.performance.title') }}
            </h2>

            <div class="about-system__perf-overview">
                <div class="about-system__perf-stat">
                    <div class="about-system__perf-stat-value">{{ $performanceData['overview']['total_requests'] }}</div>
                    <div class="about-system__perf-stat-label">{{ __('admin-about-system.charts.total_requests') }}</div>
                </div>
                <div class="about-system__perf-stat">
                    <div class="about-system__perf-stat-value">{{ $performanceData['overview']['avg_response_time'] }}<small>ms</small></div>
                    <div class="about-system__perf-stat-label">{{ __('admin-about-system.charts.avg_response') }}</div>
                </div>
                <div class="about-system__perf-stat">
                    <div class="about-system__perf-stat-value">{{ $performanceData['overview']['avg_db_time'] }}<small>ms</small></div>
                    <div class="about-system__perf-stat-label">{{ __('admin-about-system.charts.avg_db_time') }}</div>
                </div>
                <div class="about-system__perf-stat">
                    <div class="about-system__perf-stat-value">{{ $performanceData['overview']['avg_memory'] }}<small>MB</small></div>
                    <div class="about-system__perf-stat-label">{{ __('admin-about-system.charts.avg_memory') }}</div>
                </div>
                <div class="about-system__perf-stat">
                    <div class="about-system__perf-stat-value">{{ $performanceData['overview']['routes_count'] }}</div>
                    <div class="about-system__perf-stat-label">{{ __('admin-about-system.charts.routes_tracked') }}</div>
                </div>
                <div class="about-system__perf-stat">
                    <div class="about-system__perf-stat-value">{{ $performanceData['overview']['widgets_count'] }}</div>
                    <div class="about-system__perf-stat-label">{{ __('admin-about-system.charts.widgets_tracked') }}</div>
                </div>
            </div>

            @if ($performanceData['overview']['last_updated'])
                <div class="about-system__perf-updated">
                    {{ __('admin-about-system.charts.last_updated') }}: {{ $performanceData['overview']['last_updated'] }}
                </div>
            @endif
        </section>

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
            <div class="about-system__panels about-system__panels--charts">
                @foreach ($charts as $chartData)
                    <section class="about-system__panel about-system__panel--chart">
                        <h2 class="about-system__panel-title">
                            <x-icon path="{{ $chartData['icon'] }}" />
                            {{ $chartData['title'] }}
                        </h2>
                        <div class="about-system__chart-container">
                            {!! $chartData['chart']->container() !!}
                        </div>
                        {!! $chartData['chart']->script() !!}
                    </section>
                @endforeach
            </div>
        @endif
    @else
        <section class="about-system__panel about-system__panel--full">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.gauge" />
                {{ __('admin-about-system.sections.performance.title') }}
            </h2>
            <div class="about-system__no-data">
                <x-icon path="ph.regular.chart-line" />
                <p>{{ __('admin-about-system.charts.no_data') }}</p>
                <small>{{ __('admin-about-system.charts.no_data_hint') }}</small>
            </div>
        </section>
    @endif

    <div class="about-system__panels">
        <section class="about-system__panel">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.info" />
                {{ __('admin-about-system.sections.system_info.title') }}
            </h2>

            <div class="about-system__grid">
                <div class="about-system__grid-item">
                    <div class="about-system__grid-label">{{ __('admin-about-system.labels.author') }}</div>
                    <div class="about-system__grid-value">
                        <a href="https://github.com/FlamesONE" target="_blank">
                            {{ explode(' <', $systemInfo['author'])[0] ?? $systemInfo['author'] }}
                        </a>
                    </div>
                </div>

                <div class="about-system__grid-item">
                    <div class="about-system__grid-label">{{ __('admin-about-system.labels.project_link') }}</div>
                    <div class="about-system__grid-value">
                        <a href="{{ $systemInfo['project_link'] }}" target="_blank">GitHub</a>
                    </div>
                </div>

                <div class="about-system__grid-item">
                    <div class="about-system__grid-label">{{ __('admin-about-system.labels.license') }}</div>
                    <div class="about-system__grid-value">
                        <span class="badge primary">{{ $systemInfo['license'] }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-system__panel">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.code" />
                {{ __('admin-about-system.sections.php_info.title') }}
            </h2>

            <div class="about-system__grid">
                @foreach (['version', 'memory_limit', 'max_execution_time', 'upload_max_filesize', 'post_max_size', 'opcache'] as $key)
                    @if (isset($phpInfo[$key]))
                        @php
                            $hasWarning = isset($phpWarnings[$key]);
                            $warningMessage = $hasWarning ? $phpWarnings[$key] : '';
                        @endphp

                        <div
                            class="about-system__grid-item {{ $hasWarning ? 'about-system__grid-item--warning' : '' }}">
                            <div class="about-system__grid-label">
                                {{ __('admin-about-system.labels.' . $key) }}
                                @if ($hasWarning)
                                    <x-icon path="ph.regular.warning" class="icon"
                                        data-tooltip="{{ $warningMessage }}" />
                                @endif
                            </div>
                            <div class="about-system__grid-value">
                                @if ($key === 'version')
                                    <span
                                        class="about-system__badge {{ $phpVersionValid ? 'about-system__badge--success' : 'about-system__badge--warning' }}">
                                        {{ $phpInfo[$key] }}
                                    </span>
                                @elseif($key === 'opcache' || $key === 'jit')
                                    <span
                                        class="about-system__badge {{ $phpInfo[$key] === 'Enabled' ? 'about-system__badge--success' : 'about-system__badge--warning' }}">
                                        {{ $phpInfo[$key] }}
                                    </span>
                                @else
                                    {{ $phpInfo[$key] }}
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </div>

    <div class="about-system__panels">
        <section class="about-system__panel">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.database" />
                {{ __('admin-about-system.sections.server_info.title') }}
            </h2>

            <div class="about-system__grid">
                @foreach ($serverInfo as $key => $value)
                    <div class="about-system__grid-item">
                        <div class="about-system__grid-label">{{ __('admin-about-system.labels.' . $key) }}</div>
                        <div class="about-system__grid-value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="about-system__panel">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.chart-line-up" />
                {{ __('admin-about-system.sections.system_health.title') }}
            </h2>

            <div class="about-system__health">
                <div class="about-system__health-item">
                    <div class="about-system__health-label">{{ __('admin-about-system.labels.memory_usage') }}</div>
                    <div class="about-system__health-bar">
                        @php
                            $memoryUsage = memory_get_usage(true);
                            $memoryLimit = ini_get('memory_limit');
                            $memoryLimitBytes = preg_replace('/[^0-9]/', '', $memoryLimit) * 1024 * 1024;
                            $memoryPercentage =
                                $memoryLimitBytes > 0 ? min(100, round(($memoryUsage / $memoryLimitBytes) * 100)) : 0;

                            $statusClass = 'about-system__health-bar-fill--success';
                            if ($memoryPercentage > 70) {
                                $statusClass = 'about-system__health-bar-fill--warning';
                            }
                            if ($memoryPercentage > 85) {
                                $statusClass = 'about-system__health-bar-fill--error';
                            }
                        @endphp
                        <div class="about-system__health-bar-fill {{ $statusClass }}"
                            style="width: {{ $memoryPercentage }}%"></div>
                    </div>
                    <div class="about-system__health-value">
                        {{ round($memoryUsage / 1024 / 1024, 1) }}MB / {{ $memoryLimit }}
                    </div>
                </div>

                <div class="about-system__health-item">
                    <div class="about-system__health-label">{{ __('admin-about-system.labels.disk_usage') }}</div>
                    <div class="about-system__health-bar">
                        @php
                            $diskTotal = disk_total_space(__DIR__);
                            $diskFree = disk_free_space(__DIR__);
                            $diskUsed = $diskTotal - $diskFree;
                            $diskPercentage = round(($diskUsed / $diskTotal) * 100);

                            $statusClass = 'about-system__health-bar-fill--success';
                            if ($diskPercentage > 70) {
                                $statusClass = 'about-system__health-bar-fill--warning';
                            }
                            if ($diskPercentage > 85) {
                                $statusClass = 'about-system__health-bar-fill--error';
                            }
                        @endphp
                        <div class="about-system__health-bar-fill {{ $statusClass }}"
                            style="width: {{ $diskPercentage }}%">
                        </div>
                    </div>
                    <div class="about-system__health-value">
                        {{ round($diskUsed / 1024 / 1024 / 1024, 1) }}GB /
                        {{ round($diskTotal / 1024 / 1024 / 1024, 1) }}GB
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="about-system__panels">
        <section class="about-system__panel">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.cpu" />
                {{ __('admin-about-system.sections.resources.title') }}
            </h2>

            <div class="about-system__grid">
                {{-- CPU Load --}}
                <div class="about-system__grid-item">
                    <div class="about-system__grid-label">{{ __('admin-about-system.labels.cpu_load') }}</div>
                    <div class="about-system__grid-value">
                        {{ $resourceUsage['cpu_load']['1min'] }} (1m),
                        {{ $resourceUsage['cpu_load']['5min'] }} (5m),
                        {{ $resourceUsage['cpu_load']['15min'] }} (15m)
                    </div>
                </div>

                {{-- RAM Usage --}}
                <div class="about-system__grid-item">
                    <div class="about-system__grid-label">{{ __('admin-about-system.labels.ram_usage') }}</div>
                    <div class="about-system__grid-value">
                        {{-- График заполнения --}}
                        @php
                            $ramPct = $resourceUsage['ram']['percent'];
                            $ramBarClass =
                                $ramPct > 85
                                    ? 'about-system__health-bar-fill--error'
                                    : ($ramPct > 70
                                        ? 'about-system__health-bar-fill--warning'
                                        : 'about-system__health-bar-fill--success');
                        @endphp
                        <div class="about-system__health-bar">
                            <div class="about-system__health-bar-fill {{ $ramBarClass }}"
                                style="width: {{ $ramPct }}%">
                            </div>
                        </div>
                        {{ \Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper::formatBytes($resourceUsage['ram']['used']) }}
                        /
                        {{ \Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper::formatBytes($resourceUsage['ram']['total']) }}
                        ({{ $ramPct }}%)
                    </div>
                </div>
            </div>
        </section>

        <section class="about-system__panel about-system__panel--full">
            <h2 class="about-system__panel-title">
                <x-icon path="ph.regular.plug" />
                {{ __('admin-about-system.sections.requirements.title') }}
            </h2>

            <div class="about-system__requirements">
                @foreach ($requiredExtensions as $extension => $info)
                    @php
                        $itemClass = 'about-system__requirements-item';
                        $iconName = 'ph.regular.check-circle';

                        if ($info['required']) {
                            $itemClass .= ' about-system__requirements-item--required';
                        }

                        if ($info['loaded']) {
                            $itemClass .= ' about-system__requirements-item--loaded';
                        } else {
                            $iconName = 'ph.regular.x-circle';
                            $itemClass .= $info['required']
                                ? ' about-system__requirements-item--not-loaded'
                                : ' about-system__requirements-item--warning';
                        }
                    @endphp

                    <div class="{{ $itemClass }}">
                        <div class="about-system__requirements-icon">
                            <x-icon path="{{ $iconName }}" />
                        </div>
                        <div class="about-system__requirements-content">
                            <div class="about-system__requirements-name">
                                {{ $extension }}
                                @if ($info['required'])
                                    <span class="about-system__requirements-required"
                                        data-tooltip="{{ __('admin-about-system.requirements.required_extension') }}">
                                        <x-icon path="ph.regular.info" />
                                    </span>
                                @endif
                            </div>
                            <div class="about-system__requirements-description">{{ $info['description'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
