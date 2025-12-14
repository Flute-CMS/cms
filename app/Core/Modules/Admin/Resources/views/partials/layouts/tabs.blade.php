@php
    $useLazyLoad = $lazyload ?? false;
    $sticky = $sticky ?? true;
    $parentSlug = $parentSlug ?? '';
    $uniqueSlug = $parentSlug ? $parentSlug . '_' . $slug : $slug;
@endphp

<x-tabs :name="$uniqueSlug" :pills="$pills ?? false" :sticky="$sticky ?? true" yoyo:ignore>
    <x-slot:headings>
        @foreach ($tabs as $name => $tab)
            @php
                $slugName = $tab['slug'] ?? \Illuminate\Support\Str::slug($name);
                $uniqueSlugName = $uniqueSlug . '__' . $slugName;
                $isActive = $activeTab === $slugName || ($tab['active'] ?? false);
            @endphp

            @if ($useLazyLoad)
                <x-tab-heading name="{{ $uniqueSlugName }}" :badge="$tab['badge'] ?? null" :active="$isActive"
                    url="{{ url($templateSlug)->withGet()->removeParams(['yoyo-id', 'component'])->addParams(['tab-'.$slug => $slugName])->get() }}" hx-include="none"
                    :shouldTrigger="false" hx-boost="true" 
                    hx-target="#tab__{{ $uniqueSlugName }}"
                    hx-select="#tab__{{ $uniqueSlugName }}" hx-push-url="true"
                    hx-swap="{{ $morph ? 'morph:outerHTML transition:true' : 'outerHTML transition:true' }}" hx-params="not yoyo-id,component">

                    @if ($tab['icon'] ?? false)
                        <x-icon path="{{ $tab['icon'] }}" />
                    @endif

                    {!! __($tab['title'] ?? $name) !!}
                </x-tab-heading>
            @else
                <x-tab-heading name="{{ $uniqueSlugName }}" :badge="$tab['badge'] ?? null" :active="$isActive" :shouldTrigger="false">
                    @if ($tab['icon'] ?? false)
                        <x-icon path="{{ $tab['icon'] }}" />
                    @endif

                    {!! __($tab['title'] ?? $name) !!}
                </x-tab-heading>
            @endif
        @endforeach
    </x-slot:headings>
</x-tabs>

<x-tab-body name="{{ $uniqueSlug }}" class="mb-3 mt-3">
    @foreach ($tabs as $name => $tab)
        @php
            $slugName = $tab['slug'] ?? \Illuminate\Support\Str::slug($name);
            $uniqueSlugName = $uniqueSlug . '__' . $slugName;
            $isActive = $activeTab === $slugName || ($tab['active'] ?? false);
        @endphp

        <x-tab-content name="{{ $uniqueSlugName }}" :active="$isActive" id="tab__{{ $uniqueSlugName }}" class="{{ $useLazyLoad && !$isActive ? 'lazy-content' : '' }}">
            @if (! $useLazyLoad)
                @foreach ($tab['forms'] as $form)
                    @foreach ($form as $result)
                        {!! $result !!}
                    @endforeach
                @endforeach
            @else
                @if ($isActive)
                    @foreach ($tab['forms'] as $form)
                        @foreach ($form as $result)
                            {!! $result !!}
                        @endforeach
                    @endforeach
                @else
                    <div class="row gx-3 gy-3 tab-skeleton-content">
                        <div class="col-md-8">
                            <div class="tabs-skeleton-card">
                                <div class="tabs-skeleton-card__header">
                                    <div class="skeleton tabs-skeleton-card__icon"></div>
                                    <div class="tabs-skeleton-card__title-group">
                                        <div class="skeleton tabs-skeleton-card__title"></div>
                                        <div class="skeleton tabs-skeleton-card__subtitle"></div>
                                    </div>
                                </div>
                                <div class="tabs-skeleton-card__content">
                                    <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                                        <div class="tabs-skeleton-card__field">
                                            <div class="skeleton tabs-skeleton-card__label"></div>
                                            <div class="skeleton tabs-skeleton-card__input"></div>
                                        </div>
                                        <div class="tabs-skeleton-card__field">
                                            <div class="skeleton tabs-skeleton-card__label"></div>
                                            <div class="skeleton tabs-skeleton-card__input"></div>
                                        </div>
                                    </div>
                                    <div class="tabs-skeleton-card__row">
                                        <div class="tabs-skeleton-card__field">
                                            <div class="skeleton tabs-skeleton-card__label"></div>
                                            <div class="skeleton tabs-skeleton-card__textarea"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tabs-skeleton-card">
                                <div class="tabs-skeleton-card__header">
                                    <div class="skeleton tabs-skeleton-card__icon"></div>
                                    <div class="tabs-skeleton-card__title-group">
                                        <div class="skeleton tabs-skeleton-card__title"></div>
                                    </div>
                                </div>
                                <div class="tabs-skeleton-card__content">
                                    <div class="tabs-skeleton-card__toggle">
                                        <div class="skeleton tabs-skeleton-card__toggle-switch"></div>
                                        <div class="skeleton tabs-skeleton-card__toggle-label"></div>
                                    </div>
                                    <div class="tabs-skeleton-card__toggle">
                                        <div class="skeleton tabs-skeleton-card__toggle-switch"></div>
                                        <div class="skeleton tabs-skeleton-card__toggle-label"></div>
                                    </div>
                                    <div class="tabs-skeleton-card__toggle">
                                        <div class="skeleton tabs-skeleton-card__toggle-switch"></div>
                                        <div class="skeleton tabs-skeleton-card__toggle-label"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="tabs-skeleton-card">
                                <div class="tabs-skeleton-card__header">
                                    <div class="skeleton tabs-skeleton-card__icon"></div>
                                    <div class="tabs-skeleton-card__title-group">
                                        <div class="skeleton tabs-skeleton-card__title" style="width: 35%"></div>
                                        <div class="skeleton tabs-skeleton-card__subtitle" style="width: 20%"></div>
                                    </div>
                                </div>
                                <div class="tabs-skeleton-card__content">
                                    <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                                        <div class="tabs-skeleton-card__field">
                                            <div class="skeleton tabs-skeleton-card__label"></div>
                                            <div class="skeleton tabs-skeleton-card__input"></div>
                                        </div>
                                        <div class="tabs-skeleton-card__field">
                                            <div class="skeleton tabs-skeleton-card__label"></div>
                                            <div class="skeleton tabs-skeleton-card__input"></div>
                                        </div>
                                        <div class="tabs-skeleton-card__field">
                                            <div class="skeleton tabs-skeleton-card__label"></div>
                                            <div class="skeleton tabs-skeleton-card__input"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </x-tab-content>
    @endforeach
</x-tab-body>