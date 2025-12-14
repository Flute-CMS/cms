<div class="logs-viewer">
    <!-- Compact Filters -->
    <div class="logs-filters">
        <div class="filter-group">
            <label class="filter-label">{{ __('admin-logs.labels.log_file') }}</label>
            <div class="filter-select">
                <x-admin::fields.select name="logger" :options="collect($loggers)
                    ->mapWithKeys(function ($info, $name) {
                        return [$name => $name . ' (' . $info['size'] . ')'];
                    })
                    ->toArray()" value="{{ $selectedLogger }}" yoyo allowEmpty
                    placeholder="{{ __('admin-logs.labels.select_file') }}" />
            </div>
        </div>

        @if (!empty($loggers[$selectedLogger]))
            <div class="filter-group">
                <label class="filter-label">{{ __('admin-logs.labels.level') }}</label>
                <div class="filter-select">
                    <x-admin::fields.select name="level" :options="$levels" value="{{ $selectedLevel }}"
                        yoyo:change="filterByLevel($event.target.value)" yoyo allowEmpty
                        placeholder="{{ __('admin-logs.labels.filter_by_level') }}" />
                </div>
            </div>

            <div class="logs-meta">
                <div class="meta-item">
                    <x-icon path="ph.regular.file-text" size="12" />
                    <span>{{ basename($loggers[$selectedLogger]['path']) }}</span>
                </div>
                <div class="meta-item">
                    <x-icon path="ph.regular.database" size="12" />
                    <span>{{ $loggers[$selectedLogger]['size'] }}</span>
                </div>
                @if (!empty($logContent))
                    <div class="meta-item">
                        <x-icon path="ph.regular.list-bullets" size="12" />
                        <span>{{ count($logContent) }} записей</span>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Smart Logs Content -->
    <div class="logs-content">
        @if (empty($logContent))
            <div class="logs-empty">
                <div class="empty-icon">
                    <x-icon path="ph.regular.file-x" size="40" />
                </div>
                <h5>{{ __('admin-logs.labels.no_logs') }}</h5>
                <p>{{ __('admin-logs.labels.no_logs_description') }}</p>
            </div>
        @else
            <div class="logs-list">
                @foreach ($logContent as $index => $entry)
                    @php
                        $fileInfo = $entry['file_info'] ?? [];
                        $codeContext = $entry['code_context'] ?? [];
                        $contextData = json_decode($entry['context'] ?? '{}', true) ?? [];
                        $hasException = isset($contextData['exception']);
                        $hasStackTrace = strpos($entry['full_message'], 'Stack trace:') !== false;
                        $hasDetails = $hasException || $hasStackTrace || !empty($contextData) || !empty($codeContext);
                        
                        $errorType = '';
                        if (preg_match('/^([A-Z][a-zA-Z]+(?:Error|Exception|Warning))/', $entry['message'], $matches)) {
                            $errorType = $matches[1];
                        }
                        
                        $cleanMessage = preg_replace('/Stack trace:.*$/s', '', $entry['message']);
                        $cleanMessage = preg_replace('/in\s+.+\.php\s+on\s+line\s+\d+/', '', $cleanMessage);
                        $cleanMessage = trim($cleanMessage);
                    @endphp

                    <div class="log-entry {{ $entry['level'] }}-level">
                        <!-- Log Header with Key Info -->
                        <div class="log-header">
                            <div class="log-meta">
                                <span class="level-badge level-{{ $entry['level'] }}">
                                    {{ strtoupper($entry['level']) }}
                                </span>
                                <span class="log-time">{{ date('d.m H:i:s', strtotime($entry['datetime'])) }}</span>
                                <span class="log-channel">{{ $entry['channel'] }}</span>
                            </div>
                            
                            @if ($hasDetails)
                                <div class="log-actions">
                                    <button class="toggle-details" onclick="toggleDetails('{{ $index }}')">
                                        <span class="show-text">Подробнее</span>
                                        <span class="hide-text hidden">Скрыть</span>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Log Body with Smart Info -->
                        <div class="log-body">
                            <div class="log-message">
                                <div class="message-preview">
                                    @if ($errorType)
                                        <span class="error-highlight">{{ $errorType }}</span>
                                    @endif
                                    {{ $cleanMessage }}
                                </div>
                            </div>
                        </div>

                        <!-- Expandable Details -->
                        @if ($hasDetails)
                            <div id="details-{{ $index }}" class="log-details hidden">
                                <div class="details-content">
                                    @if ($hasStackTrace)
                                        <div class="stack-trace">{{ $entry['full_message'] }}</div>
                                    @endif
                                    
                                    @if (!empty($contextData))
                                        <div class="context-data">
                                            <div class="context-title">Дополнительные данные</div>
                                            <div class="context-json">{{ json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
                                        </div>
                                    @endif

                                    @if (!empty($codeContext))
                                        <div class="code-context">
                                            <div class="code-header">
                                                @if (!empty($fileInfo['relative_path']))
                                                    {{ $fileInfo['relative_path'] }}:{{ $fileInfo['line_number'] }}
                                                @else
                                                    Контекст кода
                                                @endif
                                            </div>
                                            <div class="code-content">
                                                @foreach ($codeContext as $line)
                                                    <span class="line {{ $line['is_error_line'] ? 'error-line' : '' }}">
                                                        <span class="line-num">{{ $line['line_number'] }}</span>{{ htmlspecialchars($line['content']) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Footer Actions -->
    @if (!empty($selectedLogger) && !empty($logContent))
        <div class="logs-footer">
            <div class="footer-info">
                <span>{{ count($logContent) }} записей загружено</span>
            </div>
            <div class="footer-actions">
                <x-admin::button type="outline-danger" size="small" icon="ph.regular.trash"
                    confirm="{{ __('admin-logs.clear_confirm') }}" yoyo:post="handleClearLog">
                    {{ __('admin-logs.clear_log') }}
                </x-admin::button>
            </div>
        </div>
    @endif
</div>

<script>
    function toggleDetails(id) {
        const detailsEl = document.getElementById('details-' + id);
        if (!detailsEl) return;

        const isHidden = detailsEl.classList.contains('hidden');
        detailsEl.classList.toggle('hidden');

        const button = document.querySelector(`[onclick=\"toggleDetails('${id}')\"]`);
        if (button) {
            const showText = button.querySelector('.show-text');
            const hideText = button.querySelector('.hide-text');
            if (showText) showText.classList.toggle('hidden', isHidden);
            if (hideText) hideText.classList.toggle('hidden', !isHidden);
        }
    }
</script>
