@php
    // Precompute featured and counts
    $mods = is_array($modules) ? $modules : [];
    // Keep paid first for now
    usort($mods, function($a, $b) {
        $ap = !empty($a['isPaid']);
        $bp = !empty($b['isPaid']);
        if ($ap === $bp) return 0; return $ap ? -1 : 1;
    });
    // Featured: top by downloads (fallback to the first paid)
    $sortedByDownloads = $mods;
    usort($sortedByDownloads, function($a, $b){ return ($b['downloadCount'] ?? 0) <=> ($a['downloadCount'] ?? 0); });
    $featured = array_slice($sortedByDownloads, 0, 3);
    $featuredSlugs = array_map(fn($m) => $m['slug'] ?? null, $featured);
    $allRest = array_values(array_filter($mods, fn($m) => !in_array($m['slug'] ?? null, $featuredSlugs, true)));
@endphp

<div class="admin-marketplace shadcn">
    <div class="mp-hero card-gradient">
        <div class="mp-hero-text">
            <h2>{{ __('admin-marketplace.labels.marketplace') }}</h2>
            <p class="muted">{{ __('admin-marketplace.descriptions.marketplace') }}</p>
        </div>
        <div class="mp-hero-actions">
            <x-admin::button yoyo:get="handleRefresh" type="outline-primary">
                <x-icon path="ph.bold.arrows-clockwise-bold" /> {{ __('admin-marketplace.labels.refresh') }}
            </x-admin::button>
        </div>
    </div>

    <div class="mp-toolbar card-gradient">
        <div class="mp-toolbar-row">
            <x-forms.field class="mp-search" hx-trigger="input changed delay:500ms" yoyo:get="searchChanged">
                <x-fields.input type="search" name="searchQuery" value="{{ $searchQuery }}" placeholder="{{ __('admin-marketplace.labels.search_modules') }}" />
            </x-forms.field>

            <!-- Hidden real selects for backend filtering -->
            <x-forms.field class="mp-hidden" hx-trigger="change" yoyo:get="statusFilterChanged">
                <x-fields.select name="statusFilter">
                    <option value="" @if(empty($statusFilter)) selected @endif>{{ __('admin-marketplace.labels.all_modules') }}</option>
                    <option value="installed" @if($statusFilter === 'installed') selected @endif>{{ __('admin-marketplace.labels.installed_only') }}</option>
                    <option value="notinstalled" @if($statusFilter === 'notinstalled') selected @endif>{{ __('admin-marketplace.labels.not_installed') }}</option>
                    <option value="update" @if($statusFilter === 'update') selected @endif>{{ __('admin-marketplace.labels.updates_available') }}</option>
                </x-fields.select>
            </x-forms.field>
            <x-forms.field class="mp-hidden" hx-trigger="change" yoyo:get="priceFilterChanged">
                <x-fields.select name="priceFilter">
                    <option value="" @if(empty($priceFilter)) selected @endif>{{ __('admin-marketplace.labels.all_modules') }}</option>
                    <option value="free" @if($priceFilter === 'free') selected @endif>{{ __('admin-marketplace.labels.free_only') }}</option>
                    <option value="paid" @if($priceFilter === 'paid') selected @endif>{{ __('admin-marketplace.labels.paid_only') }}</option>
                </x-fields.select>
            </x-forms.field>
            <x-forms.field class="mp-hidden" hx-trigger="change" yoyo:get="categoryFilterChanged">
                <x-fields.select name="selectedCategory">
                    <option value="" @if(empty($selectedCategory)) selected @endif>{{ __('admin-marketplace.labels.all_categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category['slug'] }}" @if($selectedCategory === $category['slug']) selected @endif>{{ $category['name'] }}</option>
                    @endforeach
                </x-fields.select>
            </x-forms.field>

            <div class="mp-controls">
                <!-- Segmented control for status -->
                <div class="segment" role="tablist" aria-label="status">
                    <button class="seg {{ empty($statusFilter) ? 'active' : '' }}" data-seg="status" data-value="">{{ __('admin-marketplace.labels.all_modules') }}</button>
                    <button class="seg {{ $statusFilter==='installed' ? 'active' : '' }}" data-seg="status" data-value="installed">{{ __('admin-marketplace.labels.installed_only') }}</button>
                    <button class="seg {{ $statusFilter==='update' ? 'active' : '' }}" data-seg="status" data-value="update">{{ __('admin-marketplace.labels.updates_available') }}</button>
                </div>

                <!-- Price chips -->
                <div class="chips-row" aria-label="price">
                    <button class="chip {{ empty($priceFilter) ? 'active' : '' }}" data-chip="price" data-value="">{{ __('admin-marketplace.labels.all_modules') }}</button>
                    <button class="chip {{ $priceFilter==='free' ? 'active' : '' }}" data-chip="price" data-value="free">{{ __('admin-marketplace.labels.free_only') }}</button>
                    <button class="chip {{ $priceFilter==='paid' ? 'active' : '' }}" data-chip="price" data-value="paid">{{ __('admin-marketplace.labels.paid_only') }}</button>
                </div>

                <!-- Categories as scrollable chips, if any -->
                @if(!empty($categories))
                    <div class="chips-row scroll" aria-label="categories">
                        <button class="chip {{ empty($selectedCategory) ? 'active' : '' }}" data-chip="category" data-value="">{{ __('admin-marketplace.labels.all_categories') }}</button>
                        @foreach($categories as $cat)
                            <button class="chip {{ $selectedCategory===$cat['slug'] ? 'active' : '' }}" data-chip="category" data-value="{{ $cat['slug'] }}">{{ $cat['name'] }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="mp-view-sort">
                <div class="view-toggle segment" role="tablist" aria-label="view">
                    <button class="seg" data-view="grid" aria-label="Grid view"><x-icon path="ph.bold.grid-four-bold" /></button>
                    <button class="seg" data-view="list" aria-label="List view"><x-icon path="ph.bold.list-bold" /></button>
                </div>
                <label class="muted text-sm">Sort</label>
                <select name="sortControl" class="sort-control">
                    <option value="featured">{{ __('admin-marketplace.labels.popular') }}</option>
                    <option value="downloads">{{ __('admin-marketplace.labels.downloads') }}</option>
                    <option value="name">A → Z</option>
                </select>
            </div>

            @if(! empty($searchQuery) || ! empty($selectedCategory) || ! empty($priceFilter) || ! empty($statusFilter))
                <div class="mp-clear">
                    <x-admin::button type="outline-primary" size="small" yoyo:post="clearFilters">
                        <x-icon path="ph.bold.x-bold" /> {{ __('admin-marketplace.labels.clear_filters') }}
                    </x-admin::button>
                </div>
            @endif
        </div>

        @php
            // Summary counters
            $total = is_array($modules) ? count($modules) : 0;
            $installedCount = 0; $updatesCount = 0; $freeCount = 0; $paidCount = 0;
            if (!empty($modules)) {
                foreach ($modules as $m) {
                    $paid = !empty($m['isPaid']);
                    $paid ? $paidCount++ : $freeCount++;
                    $k = $m['name'] ?? null;
                    if ($k && $moduleManager && $moduleManager->issetModule($k)) {
                        $mod = $moduleManager->getModule($k);
                        if ($mod->status !== 'notinstalled') {
                            $installedCount++;
                            $cur = $m['currentVersion'] ?? '0.0.0';
                            $ins = $mod->installedVersion ?? '0.0.0';
                            if (version_compare($cur, $ins, '>')) $updatesCount++;
                        }
                    }
                }
            }
        @endphp
        <div class="mp-summary">
            <span class="chip">{{ $total }} {{ __('admin-marketplace.labels.modules') ?? 'modules' }}</span>
            <span class="chip success">{{ $installedCount }} {{ __('admin-marketplace.labels.installed_only') }}</span>
            <span class="chip warning">{{ $updatesCount }} {{ __('admin-marketplace.labels.updates_available') }}</span>
            <span class="chip">{{ $freeCount }} {{ __('admin-marketplace.labels.free') }}</span>
            <span class="chip accent">{{ $paidCount }} {{ __('admin-marketplace.labels.paid') }}</span>
        </div>
    </div>

    @if(empty(config('app.flute_key')))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.messages.flute_key_not_set') }}
        </x-admin::alert>
    @elseif(empty($modules))
        <x-admin::alert type="info" withClose="false">
            {{ __('admin-marketplace.labels.no_modules_found') }}
        </x-admin::alert>
    @else
        <!-- Featured block (only in grid view) -->
        @if(!empty($featured))
            <div class="section-title">
                <h3>{{ __('admin-marketplace.labels.popular') }}</h3>
            </div>
            <div class="featured-grid">
                @foreach($featured as $module)
                    @php
                        $key = $module['name'] ?? '';
                        $isInstalled = $key && $moduleManager && $moduleManager->issetModule($key)
                            && $moduleManager->getModule($key)->status !== 'notinstalled';
                        $needsUpdate = $isInstalled && version_compare($module['currentVersion'] ?? '0.0.0', $moduleManager->getModule($key)->installedVersion ?? '0.0.0', '>');
                    @endphp
                    <div class="mp-card featured" data-name="{{ strtolower(preg_replace('/[^a-z0-9]+/i','-',$module['name'] ?? '')) }}" data-downloads="{{ $module['downloadCount'] ?? 0 }}" data-paid="{{ !empty($module['isPaid']) ? 1 : 0 }}" data-installed="{{ $isInstalled ? 1 : 0 }}">
                        <a class="cover" href="{{ url('/admin/marketplace/'.$module['slug']) }}">
                            @if(! empty($module['primaryImage']))
                                <img loading="lazy" data-src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" alt="{{ $module['name'] }}">
                            @else
                                <div class="placeholder"><x-icon path="ph.regular.package" /></div>
                            @endif
                            @if($needsUpdate)
                                <span class="flag warning">{{ __('admin-marketplace.labels.updates_available') }}</span>
                            @elseif($isInstalled)
                                <span class="flag success">{{ __('admin-marketplace.actions.installed') }}</span>
                            @endif
                        </a>
                        <div class="body">
                            <div class="top">
                                <a class="title" href="{{ url('/admin/marketplace/'.$module['slug']) }}">{{ $module['name'] }}</a>
                                <div class="badges">
                                    @if(! empty($module['isPaid']))
                                        <span class="chip accent">{{ __('admin-marketplace.labels.paid') }}</span>
                                    @else
                                        <span class="chip">{{ __('admin-marketplace.labels.free') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="meta">
                                <span class="muted">v{{ $module['currentVersion'] ?? '1.0.0' }}</span>
                                <span class="dot"></span>
                                <span class="muted">{{ $module['downloadCount'] ?? 0 }} {{ __('admin-marketplace.labels.downloads') }}</span>
                            </div>
                            <div class="actions">
                                <x-admin::button href="{{ url('/admin/marketplace/'.$module['slug']) }}" size="small" type="outline-primary">{{ __('admin-marketplace.actions.details') }}</x-admin::button>
                                @if($isInstalled && $needsUpdate)
                                    <x-admin::button href="{{ url('/admin/update') }}" size="small" type="warning">{{ __('admin-marketplace.actions.update') }}</x-admin::button>
                                @elseif($isInstalled)
                                    <x-admin::button size="small" type="success" disabled>{{ __('admin-marketplace.actions.installed') }}</x-admin::button>
                                @else
                                    <x-admin::button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                        hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                        hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                        hx-flute-confirm-type="warning" size="small" type="accent">{{ __('admin-marketplace.actions.install') }}</x-admin::button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="section-title with-sort">
            <h3>{{ __('admin-marketplace.labels.modules') }}</h3>
        </div>

        <div class="marketplace-grid" data-grid>
            @if($isLoading)
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

            @foreach($allRest as $module)
                @php
                    $key = $module['name'] ?? '';
                    $isInstalled = $key && $moduleManager && $moduleManager->issetModule($key)
                        && $moduleManager->getModule($key)->status !== 'notinstalled';
                    $needsUpdate = $isInstalled && version_compare($module['currentVersion'] ?? '0.0.0', $moduleManager->getModule($key)->installedVersion ?? '0.0.0', '>');
                @endphp
                <div class="mp-card {{ !empty($module['isPaid']) ? 'paid' : 'free' }}" data-name="{{ strtolower(preg_replace('/[^a-z0-9]+/i','-',$module['name'] ?? '')) }}" data-downloads="{{ $module['downloadCount'] ?? 0 }}" data-paid="{{ !empty($module['isPaid']) ? 1 : 0 }}" data-installed="{{ $isInstalled ? 1 : 0 }}">
                    <a class="cover" href="{{ url('/admin/marketplace/'.$module['slug']) }}">
                        @if(! empty($module['primaryImage']))
                            <img loading="lazy" data-src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" alt="{{ $module['name'] }}">
                        @else
                            <div class="placeholder"><x-icon path="ph.regular.package" /></div>
                        @endif
                        @if($needsUpdate)
                            <span class="flag warning">{{ __('admin-marketplace.labels.updates_available') }}</span>
                        @elseif($isInstalled)
                            <span class="flag success">{{ __('admin-marketplace.actions.installed') }}</span>
                        @endif
                    </a>
                    <div class="body">
                        <div class="top">
                            <a class="title" href="{{ url('/admin/marketplace/'.$module['slug']) }}">{{ $module['name'] }}</a>
                            <div class="badges">
                                @if(! empty($module['isPaid']))
                                    <span class="chip accent">{{ __('admin-marketplace.labels.paid') }}</span>
                                @else
                                    <span class="chip">{{ __('admin-marketplace.labels.free') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="meta">
                            <span class="muted">v{{ $module['currentVersion'] ?? '1.0.0' }}</span>
                            <span class="dot"></span>
                            <span class="muted">{{ $module['downloadCount'] ?? 0 }} {{ __('admin-marketplace.labels.downloads') }}</span>
                        </div>
                        <div class="actions">
                            <x-admin::button href="{{ url('/admin/marketplace/'.$module['slug']) }}" size="small" type="outline-primary">{{ __('admin-marketplace.actions.details') }}</x-admin::button>
                            @if($isInstalled && $needsUpdate)
                                <x-admin::button href="{{ url('/admin/update') }}" size="small" type="warning">{{ __('admin-marketplace.actions.update') }}</x-admin::button>
                            @elseif($isInstalled)
                                <x-admin::button size="small" type="success" disabled>{{ __('admin-marketplace.actions.installed') }}</x-admin::button>
                            @else
                                <x-admin::button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                    hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                    hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                    hx-flute-confirm-type="warning" size="small" type="accent">{{ __('admin-marketplace.actions.install') }}</x-admin::button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- List view (alternative to cards) -->
        <div class="marketplace-rows" data-rows>
            @foreach($allRest as $module)
                @php
                    $key = $module['name'] ?? '';
                    $isInstalled = $key && $moduleManager && $moduleManager->issetModule($key)
                        && $moduleManager->getModule($key)->status !== 'notinstalled';
                    $needsUpdate = $isInstalled && version_compare($module['currentVersion'] ?? '0.0.0', $moduleManager->getModule($key)->installedVersion ?? '0.0.0', '>');
                @endphp
                <div class="mp-row {{ !empty($module['isPaid']) ? 'paid' : 'free' }}" data-name="{{ strtolower(preg_replace('/[^a-z0-9]+/i','-',$module['name'] ?? '')) }}" data-downloads="{{ $module['downloadCount'] ?? 0 }}" data-paid="{{ !empty($module['isPaid']) ? 1 : 0 }}" data-installed="{{ $isInstalled ? 1 : 0 }}">
                    <a class="thumb" href="{{ url('/admin/marketplace/'.$module['slug']) }}">
                        @if(! empty($module['primaryImage']))
                            <img loading="lazy" data-src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}" alt="{{ $module['name'] }}">
                        @else
                            <div class="placeholder"><x-icon path="ph.regular.package" /></div>
                        @endif
                    </a>
                    <div class="info">
                        <a class="title" href="{{ url('/admin/marketplace/'.$module['slug']) }}">{{ $module['name'] }}</a>
                        <div class="meta">
                            @if(! empty($module['isPaid']))
                                <span class="chip accent">{{ __('admin-marketplace.labels.paid') }}</span>
                            @else
                                <span class="chip">{{ __('admin-marketplace.labels.free') }}</span>
                            @endif
                            <span class="muted">v{{ $module['currentVersion'] ?? '1.0.0' }}</span>
                            <span class="dot"></span>
                            <span class="muted">{{ $module['downloadCount'] ?? 0 }} {{ __('admin-marketplace.labels.downloads') }}</span>
                            @if($needsUpdate)
                                <span class="chip warning">{{ __('admin-marketplace.labels.updates_available') }}</span>
                            @elseif($isInstalled)
                                <span class="chip success">{{ __('admin-marketplace.actions.installed') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="actions">
                        <x-admin::button href="{{ url('/admin/marketplace/'.$module['slug']) }}" size="small" type="outline-primary">{{ __('admin-marketplace.actions.details') }}</x-admin::button>
                        @if($isInstalled && $needsUpdate)
                            <x-admin::button href="{{ url('/admin/update') }}" size="small" type="warning">{{ __('admin-marketplace.actions.update') }}</x-admin::button>
                        @elseif($isInstalled)
                            <x-admin::button size="small" type="success" disabled>{{ __('admin-marketplace.actions.installed') }}</x-admin::button>
                        @else
                            <x-admin::button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                hx-flute-confirm-type="warning" size="small" type="accent">{{ __('admin-marketplace.actions.install') }}</x-admin::button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
