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
                    <div class="tab-skeleton-content">
                        {{-- Rendered in isolation instead of via @include: an include inherits
                             every variable in scope, including $result — the *active* tab's
                             built layout, left over from the loop above. Illuminate's
                             View::gatherData() renders any Renderable it finds in view data, so
                             each lazy tab would silently re-render the whole active tab and throw
                             the result away. On a 203-row screen with 28 category tabs that was
                             5684 row renders instead of 203, and ~16s instead of 0.6s. --}}
                        {!! view('admin::partials.layouts.skeleton', [
                            'skeletons' => !empty($tab['skeletons']) ? $tab['skeletons'] : [['type' => 'generic']],
                        ])->render() !!}
                    </div>
                @endif
            @endif
        </x-tab-content>
    @endforeach
</x-tab-body>
