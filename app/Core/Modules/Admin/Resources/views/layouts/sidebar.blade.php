<aside @class([
    'sidebar',
    'collapsed' => cookie()->get('admin-sidebar-collapsed', 'false') === 'true' && !user()->device()->isMobile(),
]) hx-boost="true" hx-target="#main" hx-swap="morph:outerHTML transition:true">
    <div class="sidebar__header" hx-boost="false">
        <a href="{{ url('/') }}" class="sidebar__logo sidebar__logo-dark">
            <img src="{{ asset(config('app.logo')) }}" alt="{{ __('def.home') }}" loading="lazy">
            <p>{{ config('app.name') }}</p>
        </a>
        <a href="{{ url('/') }}" class="sidebar__logo sidebar__logo-light">
            <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" alt="{{ __('def.home') }}" loading="lazy">
            <p>{{ config('app.name') }}</p>
        </a>
        <button class="sidebar__toggle" data-tooltip="{{ __('def.toggle_sidebar') }}" data-tooltip-placement="right">
            <x-icon path="ph.regular.sidebar-simple" />
        </button>
        <button class="sidebar__toggle-mobile hamburger" aria-label="Toggle Sidebar">
            <x-icon path="ph.regular.x" />
        </button>
    </div>

    <div class="sidebar__search" hx-boost="false">
        <button class="sidebar__search-btn" id="sidebar-search-trigger">
            <x-icon path="ph.regular.magnifying-glass" class="sidebar__search-icon" />
            <span class="sidebar__search-text">{{ __('search.quick_search') }}</span>
            <kbd class="sidebar__search-kbd">Ctrl K</kbd>
        </button>
    </div>

    <div class="sidebar__container">
        <nav class="sidebar__content">
            @php
                $menuSections = app(\Flute\Admin\AdminPanel::class)->getAllMenuItems();
            @endphp

            @foreach ($menuSections as $sectionIndex => $section)
                @if (!empty($section['items']))
                    <div class="sidebar__section" data-section-id="{{ $sectionIndex }}">
                        @if (!empty($section['title']))
                            <div class="sidebar__section-title">{{ $section['title'] }}</div>
                        @endif
                        <ul class="sidebar__menu">
                            @foreach ($section['items'] as $item)
                                <x-menu-item :item="$item" />
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </nav>
    </div>
</aside>
