@php
    // Modules already filtered/sorted server-side (paid first)
    $mods = is_array($modules) ? $modules : [];
@endphp

<div class="admin-marketplace shadcn view-grid">
    <!-- Removed internal hero: header and actions already provided by Screen -->

    <form method="GET" action="{{ url('/admin/marketplace') }}" class="mp-toolbar card-gradient">
        <div class="mp-toolbar-row">
            <div class="mp-search">
                <x-fields.input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('admin-marketplace.labels.search_modules') }}" />
            </div>

            <div class="mp-controls">
                <div class="segment" role="group" aria-label="{{ __('admin-marketplace.labels.price') }}">
                    @php $p = $priceFilter; @endphp
                    <input type="radio" id="price_all" name="price" value="" @if($p==='') checked @endif>
                    <label class="seg" for="price_all">{{ __('admin-marketplace.labels.all_modules') }}</label>
                    <input type="radio" id="price_free" name="price" value="free" @if($p==='free') checked @endif>
                    <label class="seg" for="price_free">{{ __('admin-marketplace.labels.free_only') }}</label>
                    <input type="radio" id="price_paid" name="price" value="paid" @if($p==='paid') checked @endif>
                    <label class="seg" for="price_paid">{{ __('admin-marketplace.labels.paid_only') }}</label>
                </div>

                <div class="segment" role="group" aria-label="{{ __('admin-marketplace.labels.status') }}">
                    @php $s = $statusFilter; @endphp
                    <input type="radio" id="status_all" name="status" value="" @if($s==='') checked @endif>
                    <label class="seg" for="status_all">{{ __('admin-marketplace.labels.all_modules') }}</label>
                    <input type="radio" id="status_installed" name="status" value="installed" @if($s==='installed') checked @endif>
                    <label class="seg" for="status_installed">{{ __('admin-marketplace.labels.installed_only') }}</label>
                    <input type="radio" id="status_notinstalled" name="status" value="notinstalled" @if($s==='notinstalled') checked @endif>
                    <label class="seg" for="status_notinstalled">{{ __('admin-marketplace.labels.not_installed') }}</label>
                    <input type="radio" id="status_update" name="status" value="update" @if($s==='update') checked @endif>
                    <label class="seg" for="status_update">{{ __('admin-marketplace.labels.updates_available') }}</label>
                </div>

                <div>
                    <x-fields.select name="category" label="{{ __('admin-marketplace.labels.category') }}">
                        <option value="" @if(empty($selectedCategory)) selected @endif>{{ __('admin-marketplace.labels.all_categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category['slug'] }}" @if($selectedCategory === $category['slug']) selected @endif>{{ $category['name'] }}</option>
                        @endforeach
                    </x-fields.select>
                </div>

                <div class="mp-apply">
                    <x-admin::button type="primary">{{ __('admin-marketplace.actions.search') }}</x-admin::button>
                    @if(! empty($searchQuery) || ! empty($selectedCategory) || ! empty($priceFilter) || ! empty($statusFilter))
                        <a href="{{ url('/admin/marketplace') }}" class="btn btn-outline-primary">
                            <x-icon path="ph.bold.x-bold" /> {{ __('admin-marketplace.labels.clear_filters') }}
                        </a>
                    @endif
                </div>
            </div>
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
    </form>

    @if(empty(config('app.flute_key')))
        <x-admin::alert type="danger" withClose="false">
            {{ __('admin-marketplace.messages.flute_key_not_set') }}
        </x-admin::alert>
    @elseif(empty($modules))
        <x-admin::alert type="info" withClose="false">
            {{ __('admin-marketplace.labels.no_modules_found') }}
        </x-admin::alert>
    @else
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

            @foreach($mods as $module)
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
    @endif
</div>
