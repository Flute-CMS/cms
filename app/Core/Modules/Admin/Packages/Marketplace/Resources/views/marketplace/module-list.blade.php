@php
    $mods = is_array($modules) ? $modules : [];
    $total = count($mods);
    $installedCount = 0;
    $updatesCount = 0;
    $freeCount = 0;
    $paidCount = 0;
    if (!empty($mods)) {
        foreach ($mods as $m) {
            $paid = !empty($m['isPaid']);
            $paid ? $paidCount++ : $freeCount++;
            $k = $m['name'] ?? null;
            if ($k && $moduleManager && $moduleManager->issetModule($k)) {
                $mod = $moduleManager->getModule($k);
                if ($mod->status !== 'notinstalled') {
                    $installedCount++;
                    $cur = $m['currentVersion'] ?? '0.0.0';
                    $ins = $mod->installedVersion ?? '0.0.0';
                    if (version_compare($cur, $ins, '>')) {
                        $updatesCount++;
                    }
                }
            }
        }
    }
@endphp

<div class="admin-marketplace shadcn" yoyo>
    @if (!ioncube_loaded())
        <x-admin::alert type="warning" withClose="false" class="mb-3">
            <strong>{{ __('admin-marketplace.ioncube.missing_title') }}</strong>
            <div class="mt-1">{{ __('admin-marketplace.ioncube.missing_desc') }}</div>
            <div class="mt-1 text-sm">
                <a href="https://www.ioncube.com/loaders.php" target="_blank"
                    rel="noreferrer">https://www.ioncube.com/loaders.php</a>
            </div>
        </x-admin::alert>
    @endif

    @if (empty(config('app.flute_key')))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.messages.flute_key_not_set') }}
        </x-admin::alert>
    @else
        <div class="mp-layout">
            {{-- Sidebar --}}
            <aside class="mp-sidebar card-gradient">
                {{-- Search --}}
                <div class="mp-sidebar-search">
                    <x-fields.input type="search" name="q" value="{{ $searchQuery }}"
                        placeholder="{{ __('admin-marketplace.labels.search_modules') }}"
                        yoyo:on="input delay:400ms" yoyo:post="handleFilters" />
                </div>

                {{-- Categories --}}
                <nav class="mp-categories">
                    <div class="mp-sidebar-title">{{ __('admin-marketplace.labels.categories') }}</div>
                    @foreach ($categoriesWithCounts as $cat)
                        <label class="mp-cat-item {{ $categoryFilter === $cat['key'] || ($categoryFilter === '' && $cat['key'] === 'all') ? 'active' : '' }}">
                            <input type="radio" name="categoryFilter" value="{{ $cat['key'] }}"
                                yoyo:on="change" yoyo:post="handleFilters"
                                @if ($categoryFilter === $cat['key'] || ($categoryFilter === '' && $cat['key'] === 'all')) checked @endif>
                            <x-icon path="{{ $cat['icon'] }}" />
                            <span class="mp-cat-label">{{ $cat['label'] }}</span>
                            <span class="mp-cat-count">{{ $cat['count'] }}</span>
                        </label>
                    @endforeach
                </nav>

                {{-- Price Filter --}}
                <div class="mp-filter-group">
                    <div class="mp-sidebar-title">{{ __('admin-marketplace.labels.price') }}</div>
                    @php $prices = ['' => 'all_modules', 'free' => 'free_only', 'paid' => 'paid_only']; @endphp
                    @foreach ($prices as $val => $labelKey)
                        <label class="mp-filter-item {{ $priceFilter === $val ? 'active' : '' }}">
                            <input type="radio" name="price" value="{{ $val }}"
                                yoyo:on="change" yoyo:post="handleFilters"
                                @if ($priceFilter === $val) checked @endif>
                            <span>{{ __('admin-marketplace.labels.' . $labelKey) }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Status Filter --}}
                <div class="mp-filter-group">
                    <div class="mp-sidebar-title">{{ __('admin-marketplace.labels.status') }}</div>
                    @php $statuses = ['' => 'all_modules', 'installed' => 'installed_only', 'notinstalled' => 'not_installed', 'update' => 'updates_available']; @endphp
                    @foreach ($statuses as $val => $labelKey)
                        <label class="mp-filter-item {{ $statusFilter === $val ? 'active' : '' }}">
                            <input type="radio" name="status" value="{{ $val }}"
                                yoyo:on="change" yoyo:post="handleFilters"
                                @if ($statusFilter === $val) checked @endif>
                            <span>{{ __('admin-marketplace.labels.' . $labelKey) }}</span>
                        </label>
                    @endforeach
                </div>
            </aside>

            {{-- Main content --}}
            <div class="mp-main">
                {{-- Toolbar --}}
                <div class="mp-top-bar">
                    <div class="mp-summary">
                        <span class="chip">{{ $total }} {{ __('admin-marketplace.labels.modules') }}</span>
                        <span class="chip success">{{ $installedCount }} {{ __('admin-marketplace.labels.installed_only') }}</span>
                        @if ($updatesCount > 0)
                            <span class="chip warning">{{ $updatesCount }} {{ __('admin-marketplace.labels.updates_available') }}</span>
                        @endif
                    </div>
                    <div class="mp-top-controls">
                        <select name="sortBy" class="mp-sort-select" yoyo:ignore
                            yoyo:on="change" yoyo:post="handleFilters">
                            <option value="featured" @if ($sortBy === 'featured') selected @endif>{{ __('admin-marketplace.labels.sort_featured') }}</option>
                            <option value="name" @if ($sortBy === 'name') selected @endif>{{ __('admin-marketplace.labels.sort_name') }}</option>
                            <option value="free_first" @if ($sortBy === 'free_first') selected @endif>{{ __('admin-marketplace.labels.sort_free') }}</option>
                        </select>
                        <div class="mp-view-toggle">
                            <button type="button" class="mp-view-btn {{ $viewMode === 'grid' ? 'active' : '' }}"
                                yoyo:post="setViewMode('grid')">
                                <x-icon path="ph.bold.squares-four-bold" />
                            </button>
                            <button type="button" class="mp-view-btn {{ $viewMode === 'list' ? 'active' : '' }}"
                                yoyo:post="setViewMode('list')">
                                <x-icon path="ph.bold.list-bold" />
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Module Grid --}}
                @if (empty($mods))
                    <div class="mp-empty-state card-gradient">
                        <x-icon path="ph.regular.package" />
                        <p>{{ __('admin-marketplace.labels.no_modules_found') }}</p>
                    </div>
                @else
                    <div class="marketplace-grid {{ $viewMode === 'list' ? 'view-list' : 'view-grid' }}" data-grid>
                        @if ($isLoading)
                            @for ($i = 0; $i < 6; $i++)
                                <div class="mp-card skeleton">
                                    <div class="cover"></div>
                                    <div class="body">
                                        <div class="line w-70"></div>
                                        <div class="line w-40 mt-6"></div>
                                        <div class="line w-50 mt-10"></div>
                                    </div>
                                </div>
                            @endfor
                        @endif

                        @foreach ($mods as $module)
                            @php
                                $key = $module['name'] ?? '';
                                $isInstalled =
                                    $key &&
                                    $moduleManager &&
                                    $moduleManager->issetModule($key) &&
                                    $moduleManager->getModule($key)->status !== 'notinstalled';
                                $needsUpdate =
                                    $isInstalled &&
                                    version_compare(
                                        $module['currentVersion'] ?? '0.0.0',
                                        $moduleManager->getModule($key)->installedVersion ?? '0.0.0',
                                        '>',
                                    );
                                $shortDesc = $module['_shortDesc'] ?? '';
                            @endphp
                            <div class="mp-card {{ !empty($module['isPaid']) ? 'paid' : 'free' }}"
                                data-name="{{ strtolower(preg_replace('/[^a-z0-9]+/i', '-', $module['name'] ?? '')) }}"
                                data-installed="{{ $isInstalled ? 1 : 0 }}">
                                <a class="cover"
                                    href="{{ url('/admin/marketplace/' . $module['slug']) }}">
                                    @if (!empty($module['primaryImage']))
                                        <img loading="lazy"
                                            src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url') . $module['primaryImage'] }}"
                                            alt="{{ $module['name'] }}">
                                    @else
                                        <div class="placeholder"><x-icon path="ph.regular.package" /></div>
                                    @endif
                                    @if ($needsUpdate)
                                        <span class="flag warning">{{ __('admin-marketplace.labels.updates_available') }}</span>
                                    @elseif($isInstalled)
                                        <span class="flag success">{{ __('admin-marketplace.actions.installed') }}</span>
                                    @endif
                                </a>
                                <div class="body">
                                    <div class="top">
                                        <a class="title"
                                            href="{{ url('/admin/marketplace/' . $module['slug']) }}">{{ $module['name'] }}</a>
                                        <div class="badges">
                                            @if (!empty($module['isPaid']))
                                                <span class="chip accent">{{ __('admin-marketplace.labels.paid') }}</span>
                                            @else
                                                <span class="chip">{{ __('admin-marketplace.labels.free') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($shortDesc)
                                        <p class="mp-card-desc">{{ $shortDesc }}</p>
                                    @endif
                                    <div class="meta">
                                        <span class="muted">{{ $module['author'] ?? 'Flames' }}</span>
                                        <span class="dot"></span>
                                        <span class="muted">v{{ $module['currentVersion'] ?? '1.0.0' }}</span>
                                    </div>
                                </div>
                                <div class="actions">
                                    <a href="{{ url('/admin/marketplace/' . $module['slug']) }}"
                                        data-tooltip="{{ __('admin-marketplace.actions.details') }}" class="mp-details-button">
                                        <x-icon path="ph.bold.info-bold" />
                                    </a>
                                    @if ($needsUpdate)
                                        <button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                            hx-flute-confirm="{{ __('admin-marketplace.messages.update_confirm', ['module' => $module['name']]) }}"
                                            hx-flute-confirm-title="{{ __('admin-marketplace.messages.update_confirm_title') }}"
                                            hx-flute-confirm-type="warning"
                                            data-tooltip="{{ __('admin-marketplace.actions.update') }}"
                                            class="mp-install-button mp-update-button">
                                            <x-icon path="ph.bold.arrow-circle-up-bold" />
                                        </button>
                                    @elseif (!$isInstalled)
                                        <button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                            hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                            hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                            hx-flute-confirm-type="warning"
                                            data-tooltip="{{ __('admin-marketplace.actions.install') }}"
                                            class="mp-install-button">
                                            <x-icon path="ph.bold.download-simple-bold" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
