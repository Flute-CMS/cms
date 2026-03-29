@php
    $readonly = $readonly ?? false;
    $catalog = $catalog ?? false;
    $is_primary = $is_primary ?? false;
    $version = $item['version'] ?? '';
    $releaseDate = $item['release_date'] ?? null;
    $hasHistory = !empty($item['previous_versions']);
    $actionKey = $type . '_update_' . ($itemId ?? 'cms') . '_' . str_replace('.', '_', $version);
    $isCatalogCms = $catalog && $type === 'cms';
@endphp

<div class="su-cell {{ $is_primary ? 'su-cell--primary' : '' }}">
    <div class="su-cell-row">
        <div class="su-cell-info">
            <div class="su-cell-name">{{ $name }}</div>
            <div class="su-cell-ver">
                <span class="su-tag su-tag--neutral">v{{ $current_ver }}</span>
                <x-icon path="ph.bold.arrow-right-bold" class="su-cell-arrow" />
                <span class="su-tag su-tag--accent">v{{ $version }}</span>
                @if ($releaseDate)
                    <span class="su-cell-date">{{ $releaseDate }}</span>
                @endif
            </div>
        </div>
        <div class="su-cell-action">
            @if ($readonly)
                <span class="su-tag su-tag--outline">{{ __('admin-update.preview_only') }}</span>
            @elseif ($isCatalogCms)
                <x-button size="sm" type="accent" yoyo:post="handleInstallVersion"
                    yoyo:val.version="{{ $version }}"
                    hx-flute-confirm="{{ __('admin-update.install_version_confirm', ['version' => $version]) }}"
                    hx-flute-confirm-type="warning"
                    hx-flute-action-key="{{ $actionKey }}" hx-trigger="confirmed">
                    <x-icon path="ph.bold.download-simple-bold" />
                    {{ __('admin-update.install_version') }}
                </x-button>
            @elseif ($type === 'cms')
                <x-button size="sm" type="accent" yoyo:post="handleUpdate" yoyo:val.type="cms"
                    hx-flute-confirm="{{ __('admin-update.update_confirm') }}" hx-flute-confirm-type="info"
                    hx-flute-action-key="{{ $actionKey }}" hx-trigger="confirmed">
                    {{ __('admin-update.update') }}
                </x-button>
            @else
                <div yoyo:vals='{"id": "{{ $itemId }}", "version": "{{ $version }}", "type": "{{ $type }}"}'>
                    <x-button size="sm" type="accent" yoyo:post="handleUpdate"
                        hx-flute-confirm="{{ __('admin-update.update_confirm') }}" hx-flute-confirm-type="info"
                        hx-flute-action-key="{{ $actionKey }}" hx-trigger="confirmed">
                        {{ __('admin-update.update') }}
                    </x-button>
                </div>
            @endif
        </div>
    </div>

    <div class="su-cell-extra">
        <details class="su-disc">
            <summary>
                <x-icon path="ph.regular.caret-right" class="su-disc-chev" />
                {{ __('admin-update.more_info') }}
            </summary>
            <div class="su-disc-body">
                @if (!empty($item['changelog_html']))
                    @include('admin-update::components.markdown', ['html' => $item['changelog_html']])
                @elseif (!empty($item['changes']))
                    <ul class="su-clist">
                        @foreach ($item['changes'] as $change)
                            @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $change); @endphp
                            <li>@include('admin-update::components.markdown', ['markdown' => $changeItem])</li>
                        @endforeach
                    </ul>
                @elseif (!empty($item['changelog']))
                    @include('admin-update::components.markdown', ['markdown' => $item['changelog']])
                @elseif (!empty($item['description']))
                    @php
                        $desc = (string) $item['description'];
                        $hasNewlines = str_contains($desc, "\n");
                        $inlineDashCount = preg_match_all('/\s-\s+/', $desc, $__m) ?: 0;
                        $looksLikeInlineList = !$hasNewlines && $inlineDashCount > 0;
                    @endphp
                    @if ($looksLikeInlineList)
                        <ul class="su-clist">
                            @foreach (preg_split('/\s-\s+/', ltrim($desc, " -")) as $di)
                                @if (trim($di) !== '')
                                    <li>@include('admin-update::components.markdown', ['markdown' => $di])</li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        @include('admin-update::components.markdown', ['markdown' => $desc])
                    @endif
                @endif
            </div>
        </details>

        @if ($hasHistory)
            <details class="su-disc su-disc--sep">
                <summary>
                    <x-icon path="ph.regular.caret-right" class="su-disc-chev" />
                    {{ __('admin-update.version_history') }}
                    <span class="su-disc-count">{{ count($item['previous_versions']) }}</span>
                </summary>
                <div class="su-hist">
                    @foreach ($item['previous_versions'] as $v)
                        @php
                            $historyKey = $type . '_update_' . ($itemId ?? 'cms') . '_' . str_replace('.', '_', $v['version']);
                            $rawCl = $v['changelog'] ?? '';
                            $clSummary = '';
                            if (preg_match('/^###\s*Changes\s*\n(.+)/ms', $rawCl, $clMatch)) {
                                $clSummary = trim(preg_replace('/^\s*[-*]\s*/', '', trim($clMatch[1])));
                            } elseif (preg_match('/^-\s*(.+)/m', $rawCl, $clMatch)) {
                                $clSummary = trim($clMatch[1]);
                            }
                            $hasFullCl = !empty($v['changelog_html']) || !empty($v['changes']) || !empty($rawCl);
                        @endphp
                        <div class="su-hrow">
                            <div class="su-hrow-top">
                                <span class="su-hrow-ver">{{ $v['version'] }}</span>
                                @if (!empty($v['release_date']))
                                    <span class="su-hrow-date">{{ $v['release_date'] }}</span>
                                @endif
                                @if ($clSummary)
                                    <span class="su-hrow-summary">{{ \Illuminate\Support\Str::limit(strip_tags($clSummary), 80) }}</span>
                                @endif
                                <span class="su-hrow-spacer"></span>
                                @if (!$readonly)
                                    @if ($isCatalogCms || ($catalog && $type === 'cms'))
                                        <button class="su-hrow-btn" yoyo:post="handleInstallVersion"
                                            yoyo:val.version="{{ $v['version'] }}"
                                            hx-flute-confirm="{{ __('admin-update.install_version_confirm', ['version' => $v['version']]) }}"
                                            hx-flute-confirm-type="warning"
                                            hx-flute-action-key="{{ $historyKey }}" hx-trigger="confirmed">
                                            <x-icon path="ph.bold.download-simple-bold" />
                                        </button>
                                    @elseif ($type === 'cms')
                                        <button class="su-hrow-btn" yoyo:post="handleUpdate"
                                            yoyo:val.type="cms" yoyo:val.version="{{ $v['version'] }}"
                                            hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                            hx-flute-confirm-type="warning"
                                            hx-flute-action-key="{{ $historyKey }}" hx-trigger="confirmed">
                                            <x-icon path="ph.bold.download-simple-bold" />
                                        </button>
                                    @else
                                        <div class="su-hrow-btn-wrap"
                                            yoyo:vals='{"id": "{{ $itemId }}", "version": "{{ $v['version'] }}", "type": "{{ $type }}"}'>
                                            <button class="su-hrow-btn" yoyo:post="handleUpdate"
                                                hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                hx-flute-confirm-type="warning"
                                                hx-flute-action-key="{{ $historyKey }}" hx-trigger="confirmed">
                                                <x-icon path="ph.bold.download-simple-bold" />
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            @if ($hasFullCl)
                                <details class="su-hrow-details">
                                    <summary>{{ __('admin-update.more_info') }}</summary>
                                    <div class="su-hrow-body">
                                        @if (!empty($v['changelog_html']))
                                            @include('admin-update::components.markdown', ['html' => $v['changelog_html']])
                                        @elseif (!empty($v['changes']))
                                            <ul class="su-clist">
                                                @foreach ($v['changes'] as $c)
                                                    @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $c); @endphp
                                                    <li>@include('admin-update::components.markdown', ['markdown' => $changeItem])</li>
                                                @endforeach
                                            </ul>
                                        @elseif (!empty($rawCl))
                                            @include('admin-update::components.markdown', ['markdown' => $rawCl])
                                        @endif
                                    </div>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
</div>
