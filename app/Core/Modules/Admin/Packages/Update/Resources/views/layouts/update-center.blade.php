<div class="updates-panel">
    <div class="headerbar">
        <div class="channel" title="{{ __('admin-update.updates_available') }}">
            <div class="toggle-group" aria-label="{{ __('admin-update.update_channel') }}">
                <button class="toggle {{ config('app.update_channel', 'stable') === 'stable' ? 'active' : '' }}"
                    yoyo:val.channel="stable" yoyo:post="switchChannel">{{ __('admin-update.channel_stable') }}</button>
                <button class="toggle {{ config('app.update_channel', 'stable') === 'early' ? 'active' : '' }}"
                    yoyo:val.channel="early" yoyo:post="switchChannel">{{ __('admin-update.channel_early') }}</button>
            </div>
        </div>
        <div class="actions">
            <x-button size="sm" type="secondary" yoyo:post="handleCheckUpdates">
                <x-icon path="ph.bold.arrows-clockwise-bold" />
                {{ __('admin-update.check_updates') }}
            </x-button>
            @php $hasUpdates = !empty($update) || !empty($modules) || !empty($themes); @endphp
            @if ($hasUpdates)
                <x-button size="sm" type="accent" yoyo:post="handleUpdateAll"
                    hx-flute-confirm="{{ __('admin-update.update_all_confirm') }}" hx-flute-confirm-type="success">
                    <x-icon path="ph.bold.arrow-circle-up-bold" />
                    {{ __('admin-update.update_all') }}
                </x-button>
            @endif
        </div>
    </div>

    <div class="content-col">
        @if (!empty($update))
            <section class="card card-primary">
                <i class="overlay-border-shift" aria-hidden="true"></i>
                <div class="card-row">
                    <div class="info">
                        <div class="name">CMS</div>
                        <div class="sub">
                            <span class="kv">{{ __('admin-update.current') }}:</span>
                            <span class="badge neutral">v{{ $current_version }}</span>
                            <x-icon path="ph.regular.arrow-right" class="sep" />
                            <span class="kv">{{ __('admin-update.available') }}:</span>
                            <span class="badge accent">v{{ $update['version'] }}</span>
                        </div>
                        <div class="meta">{{ $update['release_date'] ?? date(default_date_format(true)) }}</div>
                    </div>
                    <div class="cta">
                        <x-button size="sm" yoyo:post="handleUpdate" yoyo:val.type="cms"
                            hx-flute-confirm="{{ __('admin-update.update_confirm') }}" hx-flute-confirm-type="info"
                            hx-flute-action-key="cms_update_{{ str_replace('.', '_', $update['version']) }}"
                            hx-trigger="confirmed" type="accent">
                            <x-icon path="ph.bold.arrow-circle-up-bold" />
                            {{ __('admin-update.update') }}
                        </x-button>
                    </div>
                </div>
                <details class="more">
                    <summary>
                        <x-icon path="ph.regular.caret-right" class="chevron" />
                        <span>{{ __('admin-update.more_info') }}</span>
                    </summary>
                    <div class="more-body">
                        @if (!empty($update['changelog_html']))
                            @include('admin-update::components.markdown', [
                                'html' => $update['changelog_html'],
                            ])
                        @elseif (!empty($update['changes']))
                            <ul class="changes">
                                @foreach ($update['changes'] as $change)
                                    @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $change); @endphp
                                    <li>@include('admin-update::components.markdown', ['markdown' => $changeItem])</li>
                                @endforeach
                            </ul>
                        @elseif (!empty($update['changelog']))
                            @include('admin-update::components.markdown', ['markdown' => $update['changelog']])
                        @endif
                    </div>
                </details>
                @if (!empty($update['previous_versions']))
                    <details class="versions">
                        <summary>
                            <x-icon path="ph.regular.caret-right" class="chevron" />
                            <span>{{ __('admin-update.version_history') }}</span>
                        </summary>
                        <div class="history-timeline">
                            @foreach ($update['previous_versions'] as $v)
                                <div class="timeline-item">
                                    <div class="timeline-header">
                                        <span class="timeline-version">v{{ $v['version'] }}</span>
                                        <span class="timeline-date">{{ $v['release_date'] ?? '' }}</span>
                                    </div>
                                    <div class="timeline-content">
                                        @if (!empty($v['changelog_html']))
                                            @include('admin-update::components.markdown', [
                                                'html' => $v['changelog_html'],
                                            ])
                                         @elseif (!empty($v['changes']))
                                             <ul>
                                                 @foreach ($v['changes'] as $c)
                                                     @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $c); @endphp
                                                     <li>@include('admin-update::components.markdown', [
                                                         'markdown' => $changeItem,
                                                     ])</li>
                                                 @endforeach
                                             </ul>
                                         @elseif (!empty($v['changelog']))
                                             @include('admin-update::components.markdown', ['markdown' => $v['changelog']])
                                        @endif
                                        {{-- <div class="timeline-actions">
                                            <button class="install-button size-sm" yoyo:post="handleUpdate"
                                                yoyo:val.type="cms" yoyo:val.version="{{ $v['version'] }}"
                                                hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                hx-flute-confirm-type="warning"
                                                hx-flute-action-key="cms_update_{{ str_replace('.', '_', $v['version']) }}"
                                                hx-trigger="confirmed">
                                                <x-icon path="ph.bold.arrow-circle-up-bold" />
                                                {{ __('admin-update.install_version') }}
                                            </button>
                                        </div> --}}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>
        @endif

        @if (!empty($modules) || !empty($themes))
            <h3 class="section-title">{{ __('admin-update.also_available') }}</h3>
        @endif

        @foreach ($modules as $moduleId => $m)
            <section class="card card-compact">
                <div class="card-row">
                    <div class="info">
                        <div class="name">{{ $m['name'] }}</div>
                        <div class="sub">
                            <span class="kv">{{ __('admin-update.current') }}:</span>
                            <span class="badge neutral">v{{ $m['current_version'] }}</span>
                            <x-icon path="ph.regular.arrow-right" class="sep" />
                            <span class="kv">{{ __('admin-update.available') }}:</span>
                            <span class="badge accent">v{{ $m['version'] }}</span>
                        </div>
                    </div>
                    <div class="cta"
                        yoyo:vals='{"id": "{{ $moduleId }}", "version": "{{ $m['version'] }}", "type": "module"}'>
                        <x-button yoyo:post="handleUpdate" hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                            hx-flute-confirm-type="info"
                            hx-flute-action-key="module_update_{{ $moduleId }}_{{ str_replace('.', '_', $m['version']) }}"
                            hx-trigger="confirmed" type="accent">
                            {{ __('admin-update.update') }}
                        </x-button>
                    </div>
                </div>
                <details class="more">
                    <summary>
                        <x-icon path="ph.regular.caret-right" class="chevron" />
                        <span>{{ __('admin-update.more_info') }}</span>
                    </summary>
                    <div class="more-body">
                        @if (!empty($m['changelog_html']))
                            @include('admin-update::components.markdown', ['html' => $m['changelog_html']])
                        @elseif (!empty($m['changes']))
                            <ul class="changes">
                                @foreach ($m['changes'] as $change)
                                    @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $change); @endphp
                                    <li>@include('admin-update::components.markdown', ['markdown' => $changeItem])</li>
                                @endforeach
                            </ul>
                        @elseif (!empty($m['changelog']))
                            @include('admin-update::components.markdown', ['markdown' => $m['changelog']])
                        @elseif (!empty($m['description']))
                            @php
                                $desc = (string) $m['description'];
                                $hasNewlines = str_contains($desc, "\n");
                                $inlineDashCount = preg_match_all('/\s-\s+/', $desc, $__m) ?: 0;
                                $looksLikeInlineList = !$hasNewlines && $inlineDashCount > 0;
                            @endphp
                            @if ($looksLikeInlineList)
                                <ul class="changes">
                                    @foreach (preg_split('/\s-\s+/', ltrim($desc, " -")) as $item)
                                        @if (trim($item) !== '')
                                            <li>@include('admin-update::components.markdown', ['markdown' => $item])</li>
                                        @endif
                                    @endforeach
                                </ul>
                            @else
                                @include('admin-update::components.markdown', [
                                    'markdown' => $desc,
                                ])
                            @endif
                        @endif
                    </div>
                </details>
                @if (!empty($m['previous_versions']))
                    <details class="versions">
                        <summary>
                            <x-icon path="ph.regular.caret-right" class="chevron" />
                            <span>{{ __('admin-update.version_history') }}</span>
                        </summary>
                        <div class="history-timeline">
                            @foreach ($m['previous_versions'] as $v)
                                <div class="timeline-item">
                                    <div class="timeline-header">
                                        <span class="timeline-version">v{{ $v['version'] }}</span>
                                        <span class="timeline-date">{{ $v['release_date'] ?? '' }}</span>
                                    </div>
                                    <div class="timeline-content">
                                        @if (!empty($v['changelog_html']))
                                            @include('admin-update::components.markdown', [
                                                'html' => $v['changelog_html'],
                                            ])
                                         @elseif (!empty($v['changes']))
                                             <ul>
                                                 @foreach ($v['changes'] as $c)
                                                     @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $c); @endphp
                                                     <li>@include('admin-update::components.markdown', [
                                                         'markdown' => $changeItem,
                                                     ])</li>
                                                 @endforeach
                                             </ul>
                                         @elseif (!empty($v['changelog']))
                                             @include('admin-update::components.markdown', ['markdown' => $v['changelog']])
                                        @endif
                                        {{-- <div class="timeline-actions"
                                            yoyo:vals='{"id": "{{ $moduleId }}", "version": "{{ $v['version'] }}", "type": "module"}'>
                                            <button class="install-button size-sm" yoyo:post="handleUpdate"
                                                hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                hx-flute-confirm-type="warning"
                                                hx-flute-action-key="module_update_{{ $moduleId }}_{{ str_replace('.', '_', $v['version']) }}"
                                                hx-trigger="confirmed">
                                                <x-icon path="ph.bold.arrow-circle-up-bold" />
                                                {{ __('admin-update.install_version') }}
                                            </button>
                                        </div> --}}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>
        @endforeach

        @foreach ($themes as $themeId => $t)
            <section class="card card-compact">
                <div class="card-row">
                    <div class="info">
                        <div class="name">{{ $t['name'] }}</div>
                        <div class="sub">
                            <span class="kv">{{ __('admin-update.current') }}:</span>
                            <span class="badge neutral">v{{ $t['current_version'] }}</span>
                            <x-icon path="ph.regular.arrow-right" class="sep" />
                            <span class="kv">{{ __('admin-update.available') }}:</span>
                            <span class="badge accent">v{{ $t['version'] }}</span>
                        </div>
                    </div>
                    <div class="cta"
                        yoyo:vals='{"id": "{{ $themeId }}", "version": "{{ $t['version'] }}", "type": "theme"}'>
                        <x-button yoyo:post="handleUpdate" hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                            hx-flute-confirm-type="info"
                            hx-flute-action-key="theme_update_{{ $themeId }}_{{ str_replace('.', '_', $t['version']) }}"
                            hx-trigger="confirmed" type="accent">
                            {{ __('admin-update.update') }}
                        </x-button>
                    </div>
                </div>
                <details class="more">
                    <summary>
                        <x-icon path="ph.regular.caret-right" class="chevron" />
                        <span>{{ __('admin-update.more_info') }}</span>
                    </summary>
                    <div class="more-body">
                        @if (!empty($t['changelog_html']))
                            @include('admin-update::components.markdown', ['html' => $t['changelog_html']])
                        @elseif (!empty($t['changes']))
                            <ul class="changes">
                                @foreach ($t['changes'] as $change)
                                    @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $change); @endphp
                                    <li>@include('admin-update::components.markdown', ['markdown' => $changeItem])</li>
                                @endforeach
                            </ul>
                        @elseif (!empty($t['changelog']))
                            @include('admin-update::components.markdown', ['markdown' => $t['changelog']])
                        @endif
                    </div>
                </details>
                @if (!empty($t['previous_versions']))
                    <details class="versions">
                        <summary>
                            <x-icon path="ph.regular.caret-right" class="chevron" />
                            <span>{{ __('admin-update.version_history') }}</span>
                        </summary>
                        <div class="history-timeline">
                            @foreach ($t['previous_versions'] as $v)
                                <div class="timeline-item">
                                    <div class="timeline-header">
                                        <span class="timeline-version">v{{ $v['version'] }}</span>
                                        <span class="timeline-date">{{ $v['release_date'] ?? '' }}</span>
                                    </div>
                                    <div class="timeline-content">
                                        @if (!empty($v['changelog_html']))
                                            @include('admin-update::components.markdown', [
                                                'html' => $v['changelog_html'],
                                            ])
                                         @elseif (!empty($v['changes']))
                                             <ul>
                                                 @foreach ($v['changes'] as $c)
                                                     @php $changeItem = preg_replace('/^\s*[-*]\s*/', '', (string) $c); @endphp
                                                     <li>@include('admin-update::components.markdown', [
                                                         'markdown' => $changeItem,
                                                     ])</li>
                                                 @endforeach
                                             </ul>
                                         @elseif (!empty($v['changelog']))
                                             @include('admin-update::components.markdown', ['markdown' => $v['changelog']])
                                        @endif
                                        {{-- <div class="timeline-actions"
                                            yoyo:vals='{"id": "{{ $themeId }}", "version": "{{ $v['version'] }}", "type": "theme"}'>
                                            <button class="install-button size-sm" yoyo:post="handleUpdate"
                                                hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                hx-flute-confirm-type="warning"
                                                hx-flute-action-key="theme_update_{{ $themeId }}_{{ str_replace('.', '_', $v['version']) }}"
                                                hx-trigger="confirmed">
                                                <x-icon path="ph.bold.arrow-circle-up-bold" />
                                                {{ __('admin-update.install_version') }}
                                            </button>
                                        </div> --}}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>
        @endforeach

        @if (empty($update) && empty($modules) && empty($themes))
            @include('admin-update::components.no-updates')
        @endif
    </div>
</div>
