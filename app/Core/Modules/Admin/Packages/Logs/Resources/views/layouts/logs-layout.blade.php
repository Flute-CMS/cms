<x-admin::card>
    <x-slot:header>
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5>{{ __('admin-logs.labels.select_file') }}</h5>
            <div class="w-50">
                <x-admin::fields.select name="logger" :options="collect($loggers)->mapWithKeys(function ($info, $name) {
    return [$name => $name.' ('.$info['size'].')'];
})->toArray()" value="{{ $selectedLogger }}" yoyo
                    allowEmpty placeholder="{{ __('admin-logs.labels.select_file') }}" />
            </div>
        </div>
    </x-slot:header>

    <div class="logs-container">
        @if(! empty($loggers[$selectedLogger]))
            <div class="logs-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="log-info">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="log-info-item">
                                <span class="log-info-label">{{ __('admin-logs.labels.log_file') }}:</span>
                                <span class="log-info-value">{{ basename($loggers[$selectedLogger]['path']) }}</span>
                            </div>
                            <div class="log-info-item">
                                <span class="log-info-label">{{ __('admin-logs.labels.size') }}:</span>
                                <span class="log-info-value">{{ $loggers[$selectedLogger]['size'] }}</span>
                            </div>
                            <div class="log-info-item">
                                <span class="log-info-label">{{ __('admin-logs.labels.modified') }}:</span>
                                <span class="log-info-value">{{ $loggers[$selectedLogger]['modified'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="log-filter mb-2">
                        <x-admin::fields.select name="level" :options="$levels" value="{{ $selectedLevel }}"
                            yoyo:change="filterByLevel($event.target.value)" yoyo allowEmpty
                            placeholder="{{ __('admin-logs.labels.filter_by_level') }}" />
                    </div>
                </div>
            </div>
        @endif

        @if(empty($logContent))
            <x-admin::alert type="info" withClose="false">
                {{ __('admin-logs.labels.no_logs') }}
            </x-admin::alert>
        @else
            <div class="table-responsive">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th width="10%">{{ __('admin-logs.labels.level') }}</th>
                            <th width="15%">{{ __('admin-logs.labels.date') }}</th>
                            <th width="10%">{{ __('admin-logs.labels.channel') }}</th>
                            <th width="55%">{{ __('admin-logs.labels.message') }}</th>
                            <th width="10%">{{ __('admin-logs.labels.details') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logContent as $index => $entry)
                                        <tr class="{{ $entry['level'] }}-level">
                                            <td>
                                                <span class="log-badge {{ $entry['level'] }}">
                                                    {{ __('admin-logs.level_labels.'.$entry['level']) }}
                                                </span>
                                            </td>
                                            <td>{{ date(default_date_format(), strtotime($entry['datetime'])) }}</td>
                                            <td>{{ $entry['channel'] }}</td>
                                            <td>
                                                <div class="log-message">
                                                    @php
                                                        $message = $entry['message'];
                                                        
                                                        $message = preg_replace('/(\?|&)accessKey=([^&\s]+)/i', '$1accessKey=***', $message);
                                                        
                                                        $isLong = mb_strlen(strip_tags($message)) > 150;
                                                        $shortMessage = $isLong ? mb_substr(strip_tags($message), 0, 150).'...' : $message;
                                                    @endphp

                                                    <div id="message-short-{{ $index }}" class="{{ $isLong ? '' : 'hidden' }}">
                                                        {{ $shortMessage }}
                                                        @if($isLong)
                                                            <button type="button" class="log-message-toggle"
                                                                onclick="toggleMessage('{{ $index }}')">
                                                                <x-icon path="ph.regular.arrows-out-simple" />
                                                                <span class="toggle-text">{{ __('admin-logs.show_more') }}</span>
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <div id="message-full-{{ $index }}" class="{{ $isLong ? 'hidden' : '' }}">
                                                        {!! $message !!}
                                                        @if($isLong)
                                                            <button type="button" class="log-message-toggle"
                                                                onclick="toggleMessage('{{ $index }}')">
                                                                <x-icon path="ph.regular.arrows-in-simple" />
                                                                <span class="toggle-text">{{ __('admin-logs.show_less') }}</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if(! empty($entry['context']))
                                                    <x-admin::button type="outline-primary" size="tiny" icon="ph.regular.code"
                                                        onclick="toggleContext('{{ $index }}')">
                                                        {{ __('admin-logs.show_context') }}
                                                    </x-admin::button>
                                                @endif
                                            </td>
                                        </tr>
                                        @if(! empty($entry['context']))
                                            <tr id="context-{{ $index }}" class="context-row">
                                                <td colspan="5">
                                                    <pre class="context-data">{{ $entry['context'] }}</pre>
                                                </td>
                                            </tr>
                                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if(! empty($selectedLogger))
        <x-slot:footer>
            <div class="d-flex justify-content-end">
                <x-admin::button type="error" size="small" icon="ph.regular.trash"
                    confirm="{{ __('admin-logs.clear_confirm') }}" yoyo:post="handleClearLog">
                    {{ __('admin-logs.clear_log') }}
                </x-admin::button>
            </div>
        </x-slot:footer>
    @endif
</x-admin::card>

<script>
    function toggleContext(id) {
        const row = document.getElementById('context-' + id);
        if (row) {
            row.classList.toggle('show');
        }
    }

    function toggleMessage(id) {
        const shortEl = document.getElementById('message-short-' + id);
        const fullEl = document.getElementById('message-full-' + id);

        if (shortEl && fullEl) {
            shortEl.classList.toggle('hidden');
            fullEl.classList.toggle('hidden');
        }
    }
</script>