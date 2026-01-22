<div class="logs-minimal">
    {{-- Toolbar --}}
    <div class="logs-toolbar">
        <div class="toolbar-left">
            <div class="file-selector">
                <x-admin::fields.select name="logger" :options="collect($loggers)
                    ->mapWithKeys(function ($info, $name) {
                        return [$name => $name];
                    })
                    ->toArray()" value="{{ $selectedLogger }}" yoyo allowEmpty
                    placeholder="{{ __('admin-logs.labels.select_file') }}" />
            </div>

            @if (!empty($loggers[$selectedLogger]))
                <div class="level-filter">
                    <x-admin::fields.select name="level" :options="$levels" value="{{ $selectedLevel }}"
                        yoyo:change="filterByLevel($event.target.value)" yoyo allowEmpty
                        placeholder="{{ __('admin-logs.labels.filter_by_level') }}" />
                </div>
            @endif
        </div>

        @if (!empty($loggers[$selectedLogger]))
            <div class="toolbar-right">
                <div class="file-meta">
                    <span class="meta-size">{{ $loggers[$selectedLogger]['size'] }}</span>
                    @if (!empty($logContent))
                        <span class="meta-divider">·</span>
                        <span class="meta-count">{{ count($logContent) }}</span>
                    @endif
                </div>

                @if (!empty($logContent))
                    <x-admin::button type="ghost" size="small" icon="ph.regular.trash"
                        confirm="{{ __('admin-logs.clear_confirm') }}" yoyo:post="handleClearLog">
                        {{ __('admin-logs.clear_log') }}
                    </x-admin::button>
                @endif
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="logs-body">
        @if (empty($logContent))
            <div class="logs-empty-state">
                <div class="empty-visual">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 12h6M12 9v6M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0"/>
                    </svg>
                </div>
                <p class="empty-title">{{ __('admin-logs.labels.no_logs') }}</p>
                <p class="empty-desc">{{ __('admin-logs.labels.no_logs_description') }}</p>
            </div>
        @else
            <div class="logs-stream">
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

                        $levelClass = match($entry['level']) {
                            'error', 'critical', 'alert', 'emergency' => 'level-error',
                            'warning' => 'level-warning',
                            'info' => 'level-info',
                            'notice' => 'level-notice',
                            default => 'level-debug'
                        };

                        // Данные для копирования
                        $copyData = [
                            'level' => strtoupper($entry['level']),
                            'datetime' => $entry['datetime'],
                            'channel' => $entry['channel'],
                            'message' => $entry['message'],
                            'file' => $fileInfo['relative_path'] ?? null,
                            'line' => $fileInfo['line_number'] ?? null,
                            'full_message' => $entry['full_message'] ?? null,
                            'context' => $contextData,
                            'code' => $codeContext,
                        ];
                    @endphp

                    <article class="log-item {{ $levelClass }}" data-index="{{ $index }}" data-log='@json($copyData)'>
                        <div class="log-indicator"></div>

                        <div class="log-main">
                            <header class="log-head">
                                <div class="log-level">{{ strtoupper(substr($entry['level'], 0, 3)) }}</div>
                                <time class="log-time">{{ date('H:i:s', strtotime($entry['datetime'])) }}</time>
                                <span class="log-date">{{ date('d.m', strtotime($entry['datetime'])) }}</span>
                                @if ($entry['channel'] !== 'app')
                                    <span class="log-channel">{{ $entry['channel'] }}</span>
                                @endif
                            </header>

                            <div class="log-content">
                                @if ($errorType)
                                    <span class="log-type">{{ $errorType }}</span>
                                @endif
                                <span class="log-msg">{{ \Flute\Core\Support\FluteStr::limit($cleanMessage, 200) }}</span>
                            </div>

                            @if (!empty($fileInfo['relative_path']))
                                <div class="log-source">
                                    <span class="source-path">{{ $fileInfo['relative_path'] }}</span>
                                    @if (!empty($fileInfo['line_number']))
                                        <span class="source-line">:{{ $fileInfo['line_number'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="log-actions">
                            <button class="log-action-btn" onclick="copyLog({{ $index }})" data-tooltip="{{ __('def.copy') }}" title="Копировать">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                            @if ($hasDetails)
                                <button class="log-action-btn" onclick="toggleLogDetails({{ $index }})" aria-expanded="false">
                                    <svg class="expand-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 9l6 6 6-6"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </article>

                    @if ($hasDetails)
                        <div class="log-details" id="log-details-{{ $index }}" hidden>
                            <div class="details-inner">
                                @if (!empty($codeContext))
                                    <section class="detail-section">
                                        <header class="detail-header">
                                            <h4 class="detail-title">
                                                @if (!empty($fileInfo['relative_path']))
                                                    {{ basename($fileInfo['relative_path']) }}
                                                @else
                                                    Code
                                                @endif
                                            </h4>
                                            <button class="copy-section-btn" onclick="copySection(this, 'code')" title="Копировать код">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                </svg>
                                            </button>
                                        </header>
                                        <pre class="code-block"><code>@foreach ($codeContext as $line)<span class="code-line {{ $line['is_error_line'] ? 'error-line' : '' }}"><span class="line-num">{{ $line['line_number'] }}</span>{{ htmlspecialchars($line['content']) }}</span>
@endforeach</code></pre>
                                    </section>
                                @endif

                                @if ($hasStackTrace)
                                    <section class="detail-section">
                                        <header class="detail-header">
                                            <h4 class="detail-title">Stack Trace</h4>
                                            <button class="copy-section-btn" onclick="copySection(this, 'trace')" title="Копировать trace">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                </svg>
                                            </button>
                                        </header>
                                        <pre class="trace-block">{{ $entry['full_message'] }}</pre>
                                    </section>
                                @endif

                                @if (!empty($contextData))
                                    <section class="detail-section">
                                        <header class="detail-header">
                                            <h4 class="detail-title">Context</h4>
                                            <button class="copy-section-btn" onclick="copySection(this, 'json')" title="Копировать контекст">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                                </svg>
                                            </button>
                                        </header>
                                        <pre class="json-block">{{ json_encode($contextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </section>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
function toggleLogDetails(index) {
    const details = document.getElementById('log-details-' + index);
    const item = document.querySelector(`.log-item[data-index="${index}"]`);
    const button = item?.querySelector('.log-action-btn[aria-expanded]');

    if (!details) return;

    const isHidden = details.hidden;
    details.hidden = !isHidden;
    item?.classList.toggle('expanded', isHidden);
    button?.setAttribute('aria-expanded', isHidden);
}

function normalizePath(path) {
    if (!path) return '';
    return path.replace(/\\\\/g, '/').replace(/\\/g, '/');
}

function copyLog(index) {
    const item = document.querySelector(`.log-item[data-index="${index}"]`);
    if (!item) return;

    const data = JSON.parse(item.dataset.log);
    let output = [];

    // Header
    output.push(`## [${data.level}] ${data.datetime}`);
    output.push(`**Channel:** ${data.channel}`);
    output.push('');

    // Message
    output.push('### Message');
    output.push('```');
    output.push(data.message);
    output.push('```');
    output.push('');

    // File location
    if (data.file) {
        output.push('### Location');
        output.push(`- **File:** \`${normalizePath(data.file)}\``);
        if (data.line) {
            output.push(`- **Line:** ${data.line}`);
        }
        output.push('');
    }

    // Code context with error highlighting
    if (data.code && data.code.length > 0) {
        const fileName = data.file ? data.file.split(/[/\\]/).pop() : 'code';
        output.push(`### Code Context`);
        output.push('```php');
        data.code.forEach(line => {
            const marker = line.is_error_line ? '>>> ' : '    ';
            const lineNum = String(line.line_number).padStart(3, ' ');
            output.push(`${marker}${lineNum} | ${line.content}`);
        });
        output.push('```');
        output.push('');
    }

    // Context data
    if (data.context && Object.keys(data.context).length > 0) {
        output.push('### Context');
        output.push('```json');
        output.push(JSON.stringify(data.context, null, 2).replace(/\\\\/g, '/').replace(/\\/g, '/'));
        output.push('```');
        output.push('');
    }

    // Stack trace (normalize paths)
    if (data.full_message && data.full_message.includes('Stack trace:')) {
        output.push('### Stack Trace');
        output.push('```');
        const normalized = data.full_message
            .replace(/\\\\/g, '/')
            .replace(/\\/g, '/');
        output.push(normalized);
        output.push('```');
    }

    const text = output.join('\n');

    navigator.clipboard.writeText(text).then(() => {
        showCopyFeedback(item.querySelector('.log-action-btn'));
    });
}

function copySection(btn, type) {
    const section = btn.closest('.detail-section');
    const pre = section.querySelector('pre');
    if (!pre) return;

    let text = pre.textContent;

    // Normalize paths
    text = text.replace(/\\\\/g, '/').replace(/\\/g, '/');

    navigator.clipboard.writeText(text).then(() => {
        showCopyFeedback(btn);
    });
}

function showCopyFeedback(btn) {
    if (!btn) return;
    btn.classList.add('copied');
    setTimeout(() => btn.classList.remove('copied'), 1500);
}
</script>
