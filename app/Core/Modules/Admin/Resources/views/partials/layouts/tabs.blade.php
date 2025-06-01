@php
    $useLazyLoad = $lazyload ?? false;
    $sticky = $sticky ?? true;
@endphp

<x-tabs :name="$slug" :pills="$pills ?? false" :sticky="$sticky ?? true" yoyo:ignore>
    <x-slot:headings>
        @foreach ($tabs as $name => $tab)
            @php
                $slugName = $tab['slug'] ?? \Illuminate\Support\Str::slug($name);
                $isActive = $activeTab === $slugName || ($tab['active'] ?? false);
            @endphp

            @if ($useLazyLoad)
                <x-tab-heading name="{{ $slugName }}" :badge="$tab['badge'] ?? null" :active="$isActive"
                    url="{{ url($templateSlug)->setParams(['tab-'.$slug => $slugName])->get() }}" hx-include="none"
                    :shouldTrigger="false" hx-boost="true" hx-select="#tab__{{ $slugName }}" hx-push-url="true"
                    hx-swap="{{ $morph ? 'morph:outerHTML transition:true' : 'outerHTML transition:true' }}" hx-params="not yoyo-id">

                    @if ($tab['icon'] ?? false)
                        <x-icon path="{{ $tab['icon'] }}" />
                    @endif

                    {!! __($tab['title'] ?? $name) !!}
                </x-tab-heading>
            @else
                <x-tab-heading name="{{ $slugName }}" :badge="$tab['badge'] ?? null" :active="$isActive" :shouldTrigger="false">
                    @if ($tab['icon'] ?? false)
                        <x-icon path="{{ $tab['icon'] }}" />
                    @endif

                    {!! __($tab['title'] ?? $name) !!}
                </x-tab-heading>
            @endif
        @endforeach
    </x-slot:headings>
</x-tabs>

<x-tab-body name="{{ $slug }}" class="mb-3 mt-3">
    @foreach ($tabs as $name => $tab)
        @php
            $slugName = $tab['slug'] ?? \Illuminate\Support\Str::slug($name);
            $isActive = $activeTab === $slugName || ($tab['active'] ?? false);
        @endphp

        <x-tab-content name="{{ $slugName }}" :active="$isActive" id="tab__{{ $slugName }}" class="{{ $useLazyLoad && !$isActive ? 'lazy-content' : '' }}">
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
                        @foreach ([8, 4, 12] as $colSize)
                            <div class="col-md-{{ $colSize }}">
                                <div class="skeleton tabs-skeleton w-100" style="height: 200px"></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </x-tab-content>
    @endforeach
</x-tab-body>