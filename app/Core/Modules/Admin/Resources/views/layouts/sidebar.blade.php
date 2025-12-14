<aside @class([
    'sidebar',
    'collapsed' => cookie()->get('admin-sidebar-collapsed', 'false') === 'true' && !user()->device()->isMobile(),
]) hx-boost="true" hx-target="#main" hx-swap="morph:outerHTML transition:true">
    <div class="sidebar__header" hx-boost="false">
        <a href="{{ url('/') }}" class="sidebar__logo-collapsed">
            <img src="{{ asset('assets/img/flute_logo-simple.svg') }}" alt="{{ __('messages.menu.home') }}" loading="lazy">
        </a>
        <a href="{{ url('/') }}" class="sidebar__logo sidebar__logo-dark">
            <img src="{{ asset(config('app.logo')) }}" alt="{{ __('messages.menu.home') }}" loading="lazy">
            <p>{{ config('app.name') }}</p>
        </a>
        <a href="{{ url('/') }}" class="sidebar__logo sidebar__logo-light">
            <img src="{{ asset(config('app.logo_light', config('app.logo'))) }}" alt="{{ __('messages.menu.home') }}" loading="lazy">
            <p>{{ config('app.name')  }}</p>
        </a>
        <button class="sidebar__toggle">
            <x-icon path="ph.regular.sidebar-simple" />
        </button>
        <button class="sidebar__toggle-mobile hamburger" aria-label="Toggle Sidebar">
            <x-icon path="ph.regular.x" />
        </button>
    </div>

    <div class="sidebar__container">
        <div class="sidebar__section sidebar__section--recent" id="recent-pages" style="display: none;">
            <button class="sidebar__section-header" data-section="recent">
                <span class="sidebar__section-title">{{ __('admin.recent_pages') }}</span>
                <x-icon path="ph.regular.caret-down" class="sidebar__section-chevron" />
            </button>
            <div class="sidebar__section-content">
                <ul class="sidebar__recent-list" id="recent-pages-list"></ul>
            </div>
        </div>

        <nav class="sidebar__content">
            @php
                $menuSections = app(\Flute\Admin\AdminPanel::class)->getAllMenuItems();
            @endphp

            @foreach ($menuSections as $sectionIndex => $section)
                @if (!empty($section['items']))
                    <div class="sidebar__section" data-section-id="{{ $sectionIndex }}">
                        @if ($section['title'])
                            <button class="sidebar__section-header" data-section="{{ $sectionIndex }}">
                                <span class="sidebar__section-title">{{ __($section['title']) }}</span>
                                <x-icon path="ph.regular.caret-down" class="sidebar__section-chevron" />
                            </button>
                        @endif
                        <div class="sidebar__section-content @if(!$section['title']) sidebar__section-content--no-header @endif">
                            <ul class="sidebar__menu">
                                @foreach ($section['items'] as $item)
                                    <x-menu-item :item="$item" />
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
    </div>

    <div class="sidebar__footer" hx-boost="false">
        <a href="{{ url('/') }}" class="sidebar__footer-link" data-tooltip="{{ __('admin.back_to_site') }}" data-tooltip-placement="right">
            <x-icon path="ph.regular.house" />
            <span>{{ __('admin.back_to_site') }}</span>
        </a>
        <a href="{{ url('/profile/' . user()->getUrl()) }}" class="sidebar__footer-user">
            <img src="{{ url(user()->getCurrentUser()->avatar) }}" alt="{{ user()->getCurrentUser()->name }}" loading="lazy">
            <span>{{ user()->getCurrentUser()->name }}</span>
        </a>
    </div>
</aside>
