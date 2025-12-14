<div class="updates-macos">
    <div class="window">
        <div class="titlebar">
            <div class="traffic-lights">
                <span class="light close"></span>
                <span class="light minimize"></span>
                <span class="light fullscreen"></span>
            </div>
            <div class="window-title">{{ __('admin-update.title') }}</div>
            <div class="toolbar-actions">
                <x-button size="sm" type="secondary" yoyo:post="handleCheckUpdates">
                    <x-icon path="ph.bold.arrows-clockwise-bold" />
                    {{ __('admin-update.check_updates') }}
                </x-button>
                @php
                    $hasUpdates = !empty($update) || !empty($modules) || !empty($themes);
                @endphp
                @if ($hasUpdates)
                    <x-button size="sm" type="accent" yoyo:post="handleUpdateAll"
                              hx-flute-confirm="{{ __('admin-update.update_all_confirm') }}"
                              hx-flute-confirm-type="success">
                        <x-icon path="ph.bold.arrow-circle-up-bold" />
                        {{ __('admin-update.update_all') }}
                    </x-button>
                @endif
            </div>
        </div>

        <div class="toolbar">
            <div class="segmented">
                <button class="seg active" data-seg="cms">CMS</button>
                <button class="seg" data-seg="modules">{{ __('admin-update.update_modules') }}</button>
                <button class="seg" data-seg="themes">{{ __('admin-update.update_themes') }}</button>
            </div>
            <div class="search">
                <input type="search" id="update-search" placeholder="{{ __('def.search') }}" />
            </div>
        </div>

        <div class="content">
            <div class="pane left">
                <div class="list" id="update-list">
                    @if (!empty($update))
                        <div class="cell" data-kind="cms" data-key="cms">
                            <div class="cell-primary">
                                <div class="cell-title">CMS</div>
                                <div class="cell-sub">v{{ $current_version }} → v{{ $update['version'] }}</div>
                            </div>
                            <div class="cell-meta">
                                <span class="badge accent">{{ __('admin-update.available') }}</span>
                            </div>
                        </div>
                    @endif

                    @foreach ($modules as $moduleId => $m)
                        <div class="cell" data-kind="modules" data-key="{{ $moduleId }}" data-title="{{ $m['name'] }}">
                            <div class="cell-primary">
                                <div class="cell-title">{{ $m['name'] }}</div>
                                <div class="cell-sub">v{{ $m['current_version'] }} → v{{ $m['version'] }}</div>
                            </div>
                            <div class="cell-meta">
                                <span class="badge accent">{{ __('admin-update.available') }}</span>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($themes as $themeId => $t)
                        <div class="cell" data-kind="themes" data-key="{{ $themeId }}" data-title="{{ $t['name'] }}">
                            <div class="cell-primary">
                                <div class="cell-title">{{ $t['name'] }}</div>
                                <div class="cell-sub">v{{ $t['current_version'] }} → v{{ $t['version'] }}</div>
                            </div>
                            <div class="cell-meta">
                                <span class="badge accent">{{ __('admin-update.available') }}</span>
                            </div>
                        </div>
                    @endforeach

                    @if (empty($update) && empty($modules) && empty($themes))
                        @include('admin-update::components.no-updates')
                    @endif
                </div>
            </div>

            <div class="pane right">
                <div class="detail empty" id="detail-empty">
                    <x-icon path="ph.regular.list-magnifying-glass" />
                    <p>{{ __('admin-update.select_item') }}</p>
                </div>

                @if (!empty($update))
                    <section class="detail" id="detail-cms" hidden>
                        <header class="detail-header">
                            <div class="title">CMS</div>
                            <div class="meta">{{ $update['release_date'] ?? date(default_date_format(true)) }}</div>
                        </header>
                        <div class="detail-body">
                            @if (!empty($update['changelog_html']))
                                @include('admin-update::components.markdown', ['html' => $update['changelog_html']])
                            @elseif (!empty($update['changes']))
                                <ul class="changes">
                                    @foreach ($update['changes'] as $change)
                                        <li>@include('admin-update::components.markdown', ['markdown' => $change])</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <footer class="detail-footer">
                            <x-button yoyo:post="handleUpdate" yoyo:val.type="cms"
                                      hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                                      hx-flute-confirm-type="info"
                                      hx-flute-action-key="cms_update_{{ str_replace('.', '_', $update['version']) }}"
                                      hx-trigger="confirmed" type="accent">
                                <x-icon path="ph.bold.arrow-circle-up-bold" />
                                {{ __('admin-update.update') }}
                            </x-button>
                        </footer>
                    </section>
                @endif

                @foreach ($modules as $moduleId => $m)
                    <section class="detail" id="detail-mod-{{ $moduleId }}" hidden>
                        <header class="detail-header">
                            <div class="title">{{ $m['name'] }}</div>
                            <div class="meta">{{ $m['release_date'] ?? date(default_date_format(true)) }}</div>
                        </header>
                        <div class="detail-body">
                            @if (!empty($m['changelog_html']))
                                @include('admin-update::components.markdown', ['html' => $m['changelog_html']])
                            @elseif (!empty($m['changes']))
                                <ul class="changes">
                                    @foreach ($m['changes'] as $change)
                                        <li>@include('admin-update::components.markdown', ['markdown' => $change])</li>
                                    @endforeach
                                </ul>
                            @elseif (!empty($m['description']))
                                @include('admin-update::components.markdown', ['markdown' => $m['description']])
                            @endif
                        </div>
                        <footer class="detail-footer">
                            <div yoyo:vals='{"id": "{{ $moduleId }}", "version": "{{ $m['version'] }}", "type": "module"}'>
                                <x-button yoyo:post="handleUpdate"
                                          hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                                          hx-flute-confirm-type="info"
                                          hx-flute-action-key="module_update_{{ $moduleId }}_{{ str_replace('.', '_', $m['version']) }}"
                                          hx-trigger="confirmed" type="accent">
                                    <x-icon path="ph.bold.arrow-circle-up-bold" />
                                    {{ __('admin-update.update') }}
                                </x-button>
                            </div>
                        </footer>
                        @if (!empty($m['previous_versions']))
                            <details class="versions">
                                <summary>{{ __('admin-update.version_history') }}</summary>
                                <ul>
                                    @foreach ($m['previous_versions'] as $v)
                                        <li>
                                            <div class="row">
                                                <span>v{{ $v['version'] }}</span>
                                                <span class="muted">{{ $v['release_date'] ?? '' }}</span>
                                                <div class="spacer"></div>
                                                <div yoyo:vals='{"id": "{{ $moduleId }}", "version": "{{ $v['version'] }}", "type": "module"}'>
                                                    <button class="link" yoyo:post="handleUpdate"
                                                            hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                            hx-flute-confirm-type="warning"
                                                            hx-flute-action-key="module_update_{{ $moduleId }}_{{ str_replace('.', '_', $v['version']) }}"
                                                            hx-trigger="confirmed">
                                                        {{ __('admin-update.install_version') }}
                                                    </button>
                                                </div>
                                            </div>
                                            @if (!empty($v['changelog_html']))
                                                @include('admin-update::components.markdown', ['html' => $v['changelog_html']])
                                            @elseif (!empty($v['changes']))
                                                <ul class="changes">
                                                    @foreach ($v['changes'] as $c)
                                                        <li>@include('admin-update::components.markdown', ['markdown' => $c])</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </section>
                @endforeach

                @foreach ($themes as $themeId => $t)
                    <section class="detail" id="detail-th-{{ $themeId }}" hidden>
                        <header class="detail-header">
                            <div class="title">{{ $t['name'] }}</div>
                            <div class="meta">{{ $t['release_date'] ?? date(default_date_format(true)) }}</div>
                        </header>
                        <div class="detail-body">
                            @if (!empty($t['changelog_html']))
                                @include('admin-update::components.markdown', ['html' => $t['changelog_html']])
                            @elseif (!empty($t['changes']))
                                <ul class="changes">
                                    @foreach ($t['changes'] as $change)
                                        <li>@include('admin-update::components.markdown', ['markdown' => $change])</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <footer class="detail-footer">
                            <div yoyo:vals='{"id": "{{ $themeId }}", "version": "{{ $t['version'] }}", "type": "theme"}'>
                                <x-button yoyo:post="handleUpdate"
                                          hx-flute-confirm="{{ __('admin-update.update_confirm') }}"
                                          hx-flute-confirm-type="info"
                                          hx-flute-action-key="theme_update_{{ $themeId }}_{{ str_replace('.', '_', $t['version']) }}"
                                          hx-trigger="confirmed" type="accent">
                                    <x-icon path="ph.bold.arrow-circle-up-bold" />
                                    {{ __('admin-update.update') }}
                                </x-button>
                            </div>
                        </footer>
                        @if (!empty($t['previous_versions']))
                            <details class="versions">
                                <summary>{{ __('admin-update.version_history') }}</summary>
                                <ul>
                                    @foreach ($t['previous_versions'] as $v)
                                        <li>
                                            <div class="row">
                                                <span>v{{ $v['version'] }}</span>
                                                <span class="muted">{{ $v['release_date'] ?? '' }}</span>
                                                <div class="spacer"></div>
                                                <div yoyo:vals='{"id": "{{ $themeId }}", "version": "{{ $v['version'] }}", "type": "theme"}'>
                                                    <button class="link" yoyo:post="handleUpdate"
                                                            hx-flute-confirm="{{ __('admin-update.install_old_confirm') }}"
                                                            hx-flute-confirm-type="warning"
                                                            hx-flute-action-key="theme_update_{{ $themeId }}_{{ str_replace('.', '_', $v['version']) }}"
                                                            hx-trigger="confirmed">
                                                        {{ __('admin-update.install_version') }}
                                                    </button>
                                                </div>
                                            </div>
                                            @if (!empty($v['changelog_html']))
                                                @include('admin-update::components.markdown', ['html' => $v['changelog_html']])
                                            @elseif (!empty($v['changes']))
                                                <ul class="changes">
                                                    @foreach ($v['changes'] as $c)
                                                        <li>@include('admin-update::components.markdown', ['markdown' => $c])</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>

