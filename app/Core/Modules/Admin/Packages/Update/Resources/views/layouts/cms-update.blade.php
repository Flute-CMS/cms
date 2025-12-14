<div class="updates-container" role="region" aria-label="{{ __('admin-update.updates') }}">
    @if (! empty($update))
        <div class="cms-update">
            <div class="update-card">
                <div class="update-header">
                    <div class="update-title">
                        <h4 id="cms-update-title">{{ __('admin-update.cms_update') }}</h4>
                        <div class="version-badges" aria-label="{{ __('admin-update.version') }}">
                            <span class="version-badge current" aria-label="{{ __('admin-update.current') }}">v{{ $current_version }}</span>
                            <x-icon path="ph.regular.arrow-right" class="arrow-right" aria-hidden="true" />
                            <span class="version-badge" aria-label="{{ __('admin-update.available') }}">{{ $update['version'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="update-content">
                    @if (! empty($update['changelog_html']))
                        @include('admin-update::components.markdown', ['html' => $update['changelog_html']])
                    @elseif (! empty($update['changes']))
                        <ul class="changes-list" aria-label="{{ __('admin-update.changelog') }}">
                            @foreach ($update['changes'] as $change)
                                <li class="change-item">@include('admin-update::components.markdown', ['markdown' => $change])</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="update-footer">
                    <div class="update-meta">
                        <div class="meta-item date">
                            <x-icon path="ph.bold.calendar-bold" aria-hidden="true" />
                            <span>{{ $update['release_date'] ?? date(default_date_format(true)) }}</span>
                        </div>
                    </div>

                    <x-button class="update-button" yoyo:post="handleUpdate" yoyo:val.type="cms"
                              hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                              hx-flute-confirm-type="info"
                              hx-flute-action-key="cms_update_{{ str_replace('.', '_', $update['version']) }}"
                              hx-trigger="confirmed" type="accent">
                        <x-icon path="ph.bold.arrow-circle-up-bold" />
                        {{ __('admin-update.update') }}
                    </x-button>
                </div>

                @if (! empty($update['previous_versions']))
                    <details class="update-history">
                        <summary>
                            <x-icon path="ph.bold.clock-counter-clockwise-bold" aria-hidden="true" />
                            {{ __('admin-update.version_history') }}
                        </summary>
                        <div class="history-timeline">
                            <div class="timeline-item">
                                <div class="timeline-header">
                                    <span class="timeline-version" aria-label="{{ __('admin-update.version') }}">v{{ $update['version'] }}</span>
                                    <span class="timeline-date" aria-label="{{ __('admin-update.release_date') }}">{{ $update['release_date'] ?? date(default_date_format(true)) }}</span>
                                    <div class="timeline-tags" aria-label="{{ __('def.tags') }}">
                                        @if (! empty($update['tags']))
                                            @foreach ($update['tags'] as $tag)
                                                <span class="timeline-tag {{ $tag['type'] }}">{{ $tag['label'] }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                <div class="timeline-content">
                                    @if (! empty($update['changelog_html']))
                                        @include('admin-update::components.markdown', ['html' => $update['changelog_html']])
                                    @elseif (! empty($update['changes']))
                                        <ul aria-label="{{ __('admin-update.changelog') }}">
                                            @foreach ($update['changes'] as $change)
                                                <li>@include('admin-update::components.markdown', ['markdown' => $change])</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            @foreach ($update['previous_versions'] as $version)
                                <div class="timeline-item">
                                    <div class="timeline-header">
                                        <span class="timeline-version" aria-label="{{ __('admin-update.version') }}">v{{ $version['version'] }}</span>
                                        <span class="timeline-date" aria-label="{{ __('admin-update.release_date') }}">{{ $version['release_date'] ?? date(default_date_format(true)) }}</span>
                                        <div class="timeline-actions">
                                            <button class="install-button" yoyo:post="handleUpdate" yoyo:val.type="cms"
                                                    yoyo:val.version="{{ $version['version'] }}"
                                                    hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                    hx-flute-confirm-type="warning"
                                                    hx-flute-action-key="cms_update_{{ str_replace('.', '_', $version['version']) }}"
                                                    hx-trigger="confirmed">
                                                <x-icon path="ph.bold.arrow-circle-down-bold" />
                                                {{ __('admin-update.install_version') }}
                                            </button>
                                        </div>
                                        <div class="timeline-tags" aria-label="{{ __('def.tags') }}">
                                            @if (! empty($version['tags']))
                                                @foreach ($version['tags'] as $tag)
                                                    <span class="timeline-tag {{ $tag['type'] }}">{{ $tag['label'] }}</span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        @if (! empty($version['changelog_html']))
                                            @include('admin-update::components.markdown', ['html' => $version['changelog_html']])
                                        @elseif (! empty($version['changes']))
                                            <ul aria-label="{{ __('admin-update.changelog') }}">
                                                @foreach ($version['changes'] as $change)
                                                    <li>@include('admin-update::components.markdown', ['markdown' => $change])</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>
    @endif

    @if (! empty($modules))
        <div class="modules-section" role="region" aria-labelledby="modules-title">
            <h2 class="section-title" id="modules-title">
                <x-icon path="ph.bold.cube-bold" aria-hidden="true" />
                {{ __('admin-update.update_modules') }}
            </h2>
            @include('admin-update::layouts.modules-update', ['modules' => $modules])
        </div>
    @endif

    @if (! empty($themes))
        <div class="themes-section" role="region" aria-labelledby="themes-title">
            <h2 class="section-title" id="themes-title">
                <x-icon path="ph.bold.paint-brush-bold" aria-hidden="true" />
                {{ __('admin-update.update_themes') }}
            </h2>
            @include('admin-update::layouts.themes-update', ['themes' => $themes])
        </div>
    @endif
</div>