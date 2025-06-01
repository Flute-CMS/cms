<div>
    <div class="marketplace-filters">
        <div class="search-filter">
            <x-forms.label>{{ __('admin-marketplace.labels.search_modules') }}</x-forms.label>
            <x-forms.field hx-trigger="input changed delay:500ms" yoyo:get="searchChanged">
                <x-fields.input type="search" name="searchQuery" value="{{ $searchQuery }}"
                    placeholder="{{ __('admin-marketplace.labels.search_modules') }}" />
            </x-forms.field>
        </div>
        <div class="filter-categories">
            <x-forms.label>{{ __('admin-marketplace.labels.category') }}</x-forms.label>
            <x-forms.field hx-trigger="change" yoyo:get="categoryFilterChanged">
                <x-fields.select name="selectedCategory"
                    placeholder="{{ __('admin-marketplace.labels.all_categories') }}">
                    <option value="" @if(empty($selectedCategory)) selected @endif>
                        {{ __('admin-marketplace.labels.all_categories') }}
                    </option>
                    @foreach($categories as $category)
                        <option value="{{ $category['slug'] }}" @if($selectedCategory === $category['slug']) selected @endif>
                            {{ $category['name'] }}
                        </option>
                    @endforeach
                    </x-forms.select>
            </x-forms.field>
        </div>
        <div class="filter-price">
            <x-forms.label>{{ __('admin-marketplace.labels.price') }}</x-forms.label>
            <x-forms.field hx-trigger="change" yoyo:get="priceFilterChanged">
                <x-fields.select name="priceFilter" hx-trigger="change">
                    <option value="" @if(empty($priceFilter)) selected @endif>
                        {{ __('admin-marketplace.labels.all_modules') }}
                    </option>
                    <option value="free" @if($priceFilter === 'free') selected @endif>
                        {{ __('admin-marketplace.labels.free_only') }}
                    </option>
                    <option value="paid" @if($priceFilter === 'paid') selected @endif>
                        {{ __('admin-marketplace.labels.paid_only') }}
                    </option>
                    </x-forms.select>
            </x-forms.field>
        </div>
        <div class="filter-status">
            <x-forms.label>{{ __('admin-marketplace.labels.status') }}</x-forms.label>
            <x-forms.field hx-trigger="change" yoyo:get="statusFilterChanged">
                <x-fields.select name="statusFilter" hx-trigger="change">
                    <option value="" @if(empty($statusFilter)) selected @endif>
                        {{ __('admin-marketplace.labels.all_modules') }}
                    </option>
                    <option value="installed" @if($statusFilter === 'installed') selected @endif>
                        {{ __('admin-marketplace.labels.installed_only') }}
                    </option>
                    <option value="notinstalled" @if($statusFilter === 'notinstalled') selected @endif>
                        {{ __('admin-marketplace.labels.not_installed') }}
                    </option>
                    <option value="update" @if($statusFilter === 'update') selected @endif>
                        {{ __('admin-marketplace.labels.updates_available') }}
                    </option>
                    </x-forms.select>
            </x-forms.field>
        </div>
        @if(! empty($searchQuery) || ! empty($selectedCategory) || ! empty($priceFilter) || ! empty($statusFilter))
            <div class="clear-filters">
                <x-admin::button type="outline-primary" size="small" yoyo:post="clearFilters">
                    {{ __('admin-marketplace.labels.clear_filters') }}
                    <x-icon path="ph.bold.x-bold" />
                </x-admin::button>
            </div>
        @endif
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

        <div class="marketplace-list">
            @if($isLoading)
                <div class="marketplace-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('admin-marketplace.messages.loading') }}</span>
                    </div>
                    <div class="mt-2">{{ __('admin-marketplace.messages.loading') }}</div>
                </div>
            @endif
            @foreach($modules as $module)
                <article class="marketplace-module-card">
                    <div class="module-image">
                        @if(! empty($module['primaryImage']))
                            <img src="{{ str_starts_with($module['primaryImage'], 'http') ? $module['primaryImage'] : config('app.flute_market_url').$module['primaryImage'] }}"
                                alt="{{ $module['name'] }}">
                        @else
                            <div class="placeholder-icon">
                                <x-icon path="ph.regular.package" />
                            </div>
                        @endif
                    </div>
                    <div class="module-content">
                        <div class="module-title">
                            {{ $module['name'] }}
                            @if(! empty($module['isPaid']))
                                <span class="badge warning">{{ __('admin-marketplace.labels.paid') }}</span>
                            @else
                                <span class="badge success">{{ __('admin-marketplace.labels.free') }}</span>
                            @endif
                        </div>
                        @php $rawDesc = $module['description'] ?? ''; @endphp
                        <div class="module-description markdown-content @if(strlen($rawDesc) > 200) collapsed @endif">
                            {!! markdown()->parse($rawDesc ?: __('admin-marketplace.messages.no_description')) !!}
                        </div>
                        @if(strlen($rawDesc) > 200)
                            <button type="button" class="read-more" onclick="this.previousElementSibling.classList.toggle('collapsed'); this.textContent = this.textContent === 'Читать дальше' ? 'Свернуть' : 'Читать дальше';">Читать дальше</button>
                        @endif
                        <div class="module-meta">
                            <div class="version">
                                <x-icon path="ph.regular.tag" />
                                {{ __('admin-marketplace.labels.version') }}: {{ $module['currentVersion'] ?? '1.0.0' }}
                            </div>
                            @if(! empty($module['downloadCount']))
                                <div class="downloads">
                                    <x-icon path="ph.regular.download-simple" />
                                    {{ $module['downloadCount'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="module-actions">
                        <x-admin::button href="{{ url(config('app.flute_market_url').'/product/'.$module['slug']) }}"
                            size="small" target="_blank" withoutHtmx type="outline-primary">
                            {{ __('admin-marketplace.actions.details') }}
                        </x-admin::button>

                        @php
                            $key = $module['name'];
                            $isInstalled = $moduleManager && $moduleManager->issetModule($key)
                                && $moduleManager->getModule($key)->status !== 'notinstalled';
                            $needsUpdate = $isInstalled
                                && version_compare($module['currentVersion'] ?? '0.0.0', $moduleManager->getModule($key)->installedVersion ?? '0.0.0', '>');
                        @endphp

                        @if($isInstalled && $needsUpdate)
                            <x-admin::button href="{{ url('/admin/update') }}" size="small" type="warning">
                                {{ __('admin-marketplace.actions.update') }}
                            </x-admin::button>
                        @elseif($isInstalled)
                            <x-admin::button size="small" type="success" disabled>
                                {{ __('admin-marketplace.actions.installed') }}
                            </x-admin::button>
                        @else
                            <x-admin::button yoyo:post="installModule('{{ $module['slug'] }}')" hx-trigger="confirmed"
                                hx-flute-confirm="{{ __('admin-marketplace.messages.install_confirm', ['module' => $module['name']]) }}"
                                hx-flute-confirm-title="{{ __('admin-marketplace.messages.install_confirm_title') }}"
                                hx-flute-confirm-type="warning" size="small" type="accent">
                                {{ __('admin-marketplace.actions.install') }}
                            </x-admin::button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>