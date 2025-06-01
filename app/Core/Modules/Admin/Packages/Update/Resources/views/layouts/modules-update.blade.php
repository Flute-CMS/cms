<div class="updates-grid" role="list">
    @forelse($modules as $moduleId => $moduleUpdate)
    <div class="update-section" role="listitem">
        <div class="update-card">
            <div class="update-header">
                <h3 class="update-title" id="module-title-{{ $moduleId }}">{{ $moduleUpdate['name'] }}</h3>
                <div class="version-badges" aria-label="{{ __('admin-update.version') }}">
                    <span class="version-badge current"
                        aria-label="{{ __('admin-update.current') }}">v{{ $moduleUpdate['current_version'] }}</span>
                    <x-icon path="ph.regular.arrow-right" class="arrow-right" aria-hidden="true" />
                    <span class="version-badge"
                        aria-label="{{ __('admin-update.available') }}">v{{ $moduleUpdate['version'] }}</span>
                </div>
            </div>

            <div class="update-content">
                @if (! empty($moduleUpdate['changelog_html']))
                <div class="changelog-html">
                    {!! markdown()->parse($moduleUpdate['changelog_html']) !!}
                </div>
                @elseif (! empty($moduleUpdate['changes']))
                <ul class="changes-list" aria-label="{{ __('admin-update.changelog') }}">
                    @foreach ($moduleUpdate['changes'] as $change)
                    <li class="change-item">{{ markdown()->parse($change) }}</li>
                    @endforeach
                </ul>
                @elseif(! empty($moduleUpdate['description']))
                <div class="description">
                    {!! markdown()->parse($moduleUpdate['description']) !!}
                </div>
                @endif
            </div>

            <div class="update-footer">
                <div class="update-meta">
                    <div class="meta-item date">
                        <x-icon path="ph.bold.calendar-bold" aria-hidden="true" />
                        <span>{{ $moduleUpdate['release_date'] ?? date(default_date_format(true)) }}</span>
                    </div>
                    <div class="meta-item">
                        <button class="history-toggle" data-history="module-history-{{ $moduleId }}"
                            aria-expanded="false" aria-controls="module-history-{{ $moduleId }}">
                            {{ __('admin-update.version_history') }}
                            <x-icon path="ph.bold.caret-down-bold" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div yoyo:vals='{"id": "{{ $moduleId }}", "version": "{{ $moduleUpdate["version"] }}", "type": "module"}'>
                    <x-button class="update-button" yoyo:post="handleUpdate"
                        hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                        hx-flute-confirm-type="info"
                        hx-flute-action-key="module_update_{{ $moduleId }}_{{ str_replace('.', '_', $moduleUpdate['version']) }}"
                        hx-trigger="confirmed" type="accent">
                        <x-icon path="ph.bold.arrow-circle-up-bold" />
                        {{ __('admin-update.update') }}
                    </x-button>
                </div>
            </div>

            <div class="update-history" id="module-history-{{ $moduleId }}"
                data-history="module-history-{{ $moduleId }}" role="region"
                aria-labelledby="history-title-module-{{ $moduleId }}">
                <div class="history-title" id="history-title-module-{{ $moduleId }}">
                    <x-icon path="ph.bold.clock-counter-clockwise-bold" aria-hidden="true" />
                    {{ __('admin-update.version_history') }}
                </div>
                <div class="history-timeline">
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <span class="timeline-version"
                                aria-label="{{ __('admin-update.version') }}">v{{ $moduleUpdate['version'] }}</span>
                            <span class="timeline-date"
                                aria-label="{{ __('admin-update.release_date') }}">{{ $moduleUpdate['release_date'] ?? date(default_date_format(true)) }}</span>
                            <div class="timeline-tags" aria-label="{{ __('def.tags') }}">
                                @if(! empty($moduleUpdate['tags']))
                                @foreach ($moduleUpdate['tags'] as $tag)
                                <span class="timeline-tag {{ $tag['type'] }}">{{ $tag['label'] }}</span>
                                @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="timeline-content">
                            @if (! empty($moduleUpdate['changelog_html']))
                            <div class="changelog-html">
                                {!! markdown()->parse($moduleUpdate['changelog_html']) !!}
                            </div>
                            @elseif (! empty($moduleUpdate['changes']))
                            <ul aria-label="{{ __('admin-update.changelog') }}">
                                @foreach ($moduleUpdate['changes'] as $change)
                                <li>{{ markdown()->parse($change) }}</li>
                                @endforeach
                            </ul>
                            @elseif(! empty($moduleUpdate['description']))
                            <div class="description">
                                {!! markdown()->parse($moduleUpdate['description']) !!}
                            </div>
                            @endif
                        </div>
                    </div>

                    @if (! empty($moduleUpdate['previous_versions']))
                    @foreach ($moduleUpdate['previous_versions'] as $version)
                    <div class="timeline-item">
                        <div class="timeline-header">
                            <span class="timeline-version"
                                aria-label="{{ __('admin-update.version') }}">v{{ $version['version'] }}</span>
                            <span class="timeline-date"
                                aria-label="{{ __('admin-update.release_date') }}">{{ $version['release_date'] ?? date(default_date_format(true)) }}</span>

                            <div class="timeline-actions" yoyo:vals='{"id": "{{ $moduleId }}", "version": "{{ $version['version'] }}", "type": "module"}'>
                                <button class="install-button" yoyo:post="handleUpdate"
                                    hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                    hx-flute-confirm-type="warning"
                                    hx-flute-action-key="module_update_{{ $moduleId }}_{{ str_replace('.', '_', $version['version']) }}"
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
                            <div class="changelog-html">
                                {!! markdown()->parse($version['changelog_html']) !!}
                            </div>
                            @elseif (! empty($version['changes']))
                            <ul aria-label="{{ __('admin-update.changelog') }}">
                                @foreach ($version['changes'] as $change)
                                <li>{{ markdown()->parse($change) }}</li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="no-updates" role="alert">
        <x-icon path="ph.bold.check-circle-bold" aria-hidden="true" />
        <h3 class="status-title">{{ __('admin-update.all_modules_updated') }}</h3>
        <p class="status-description">{{ __('admin-update.modules_updated_description') }}</p>
    </div>
    @endforelse
</div>