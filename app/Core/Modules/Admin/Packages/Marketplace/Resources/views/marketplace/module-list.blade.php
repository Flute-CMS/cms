@php
    $mods = is_array($modules) ? $modules : [];
@endphp

<div class="admin-marketplace shadcn view-grid">
    <form class="mp-toolbar card-gradient" yoyo yoyo:on="changed change" yoyo:post="handleFilters">
        <div class="mp-toolbar-row">
            <div class="mp-search">
                <x-fields.input type="search" name="q" value="{{ $searchQuery }}"
                    placeholder="{{ __('admin-marketplace.labels.search_modules') }}" yoyo:on="input delay:400ms"
                    yoyo:post="handleFilters" />
            </div>

            <div class="mp-controls">
                <div class="segment" role="group" aria-label="{{ __('admin-marketplace.labels.price') }}">
                    @php $p = $priceFilter; @endphp
                    <input type="radio" id="price_all" name="price" value="" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($p === '') checked @endif>
                    <label class="seg" for="price_all">{{ __('admin-marketplace.labels.all_modules') }}</label>
                    <input type="radio" id="price_free" name="price" value="free" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($p === 'free') checked @endif>
                    <label class="seg" for="price_free">{{ __('admin-marketplace.labels.free_only') }}</label>
                    <input type="radio" id="price_paid" name="price" value="paid" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($p === 'paid') checked @endif>
                    <label class="seg" for="price_paid">{{ __('admin-marketplace.labels.paid_only') }}</label>
                </div>

                <div class="segment" role="group" aria-label="{{ __('admin-marketplace.labels.status') }}">
                    @php $s = $statusFilter; @endphp
                    <input type="radio" id="status_all" name="status" value="" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($s === '') checked @endif>
                    <label class="seg" for="status_all">{{ __('admin-marketplace.labels.all_modules') }}</label>
                    <input type="radio" id="status_installed" name="status" value="installed" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($s === 'installed') checked @endif>
                    <label class="seg"
                        for="status_installed">{{ __('admin-marketplace.labels.installed_only') }}</label>
                    <input type="radio" id="status_notinstalled" name="status" value="notinstalled" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($s === 'notinstalled') checked @endif>
                    <label class="seg"
                        for="status_notinstalled">{{ __('admin-marketplace.labels.not_installed') }}</label>
                    <input type="radio" id="status_update" name="status" value="update" yoyo:on="change" yoyo:post="handleFilters"
                        @if ($s === 'update') checked @endif>
                    <label class="seg"
                        for="status_update">{{ __('admin-marketplace.labels.updates_available') }}</label>
                </div>
            </div>
        </div>

        @php
            $total = is_array($modules) ? count($modules) : 0;
            $installedCount = 0;
            $updatesCount = 0;
            $freeCount = 0;
            $paidCount = 0;
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
                            if (version_compare($cur, $ins, '>')) {
                                $updatesCount++;
                            }
                        }
                    }
                }
            }
        @endphp
        <div class="mp-summary">
            <span class="chip">{{ $total }} {{ __('admin-marketplace.labels.modules') ?? 'modules' }}</span>
            <span class="chip success">{{ $installedCount }}
                {{ __('admin-marketplace.labels.installed_only') }}</span>
            <span class="chip warning">{{ $updatesCount }}
                {{ __('admin-marketplace.labels.updates_available') }}</span>
            <span class="chip">{{ $freeCount }} {{ __('admin-marketplace.labels.free') }}</span>
            <span class="chip accent">{{ $paidCount }} {{ __('admin-marketplace.labels.paid') }}</span>
        </div>
    </form>

    @if (empty(config('app.flute_key')))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.messages.flute_key_not_set') }}
        </x-admin::alert>
    @elseif(empty($modules))
        <x-admin::alert type="info" withClose="false">
            {{ __('admin-marketplace.labels.no_modules_found') }}
        </x-admin::alert>
    @else
        <div class="marketplace-grid" data-grid>
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
                @endphp
                <div class="mp-card {{ !empty($module['isPaid']) ? 'paid' : 'free' }}"
                    hx-swap="morph:outerHTML transition:true"
                    data-name="{{ strtolower(preg_replace('/[^a-z0-9]+/i', '-', $module['name'] ?? '')) }}"
                    data-downloads="{{ $module['downloadCount'] ?? 0 }}"
                    data-paid="{{ !empty($module['isPaid']) ? 1 : 0 }}" data-installed="{{ $isInstalled ? 1 : 0 }}">
                    <a class="cover" hx-boost="true" hx-target="#main" yoyo:ignore
                        href="{{ url('/admin/marketplace/' . $module['slug']) }}">
                        @if (!empty($module['primaryImage']))
                            <img loading="lazy"
                                data-src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url') . $module['primaryImage'] }}"
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
                            <a class="title" hx-boost="true" hx-target="#main" yoyo:ignore
                                href="{{ url('/admin/marketplace/' . $module['slug']) }}">{{ $module['name'] }}</a>
                            <div class="badges">
                                @if (!empty($module['isPaid']))
                                    <span class="chip accent">{{ __('admin-marketplace.labels.paid') }}</span>
                                @else
                                    <span class="chip">{{ __('admin-marketplace.labels.free') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="meta">
                            <span class="muted">{{ $module['author'] ?? 'Flames' }}</span>
                            <span class="dot"></span>
                            <span class="muted">v{{ $module['currentVersion'] ?? '1.0.0' }}</span>
                            <span class="dot"></span>
                            <span class="muted">{{ $module['downloadCount'] ?? 0 }}
                                {{ __('admin-marketplace.labels.downloads') }}</span>
                        </div>
                    </div>
                    <div class="actions">
                        <a hx-boost="true" yoyo:ignore href="{{ url('/admin/marketplace/' . $module['slug']) }}"
                            hx-target="#main" data-tooltip="{{ __('admin-marketplace.actions.details') }}"
                            class="mp-details-button">
                            <x-icon path="ph.bold.info-bold" />
                        </a>
                        @if (!$isInstalled)
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
