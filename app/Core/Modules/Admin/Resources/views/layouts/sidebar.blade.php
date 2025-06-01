<aside @class([
    'sidebar',
    'collapsed' => cookie()->get('admin-sidebar-collapsed', 'false') === 'true' && !user()->device()->isMobile(),
]) hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
    <div class="sidebar__header" hx-boost="false">
        <a href="{{ url('/') }}" class="sidebar__logo-collapsed">
            <img src="{{ asset('assets/img/flute_logo-simple.svg') }}" alt="{{ __('messages.menu.home') }}" loading="lazy">
        </a>
        <a href="{{ url('/') }}" class="sidebar__logo">
            <img src="{{ asset('assets/img/flute_logo.svg') }}" alt="{{ __('messages.menu.home') }}" loading="lazy">
        </a>
        <button class="sidebar__toggle">
            <x-icon path="ph.regular.sidebar-simple" />
        </button>
        <button class="sidebar__toggle-mobile hamburger" aria-label="Toggle Sidebar">
            <x-icon path="ph.regular.x" />
        </button>
    </div>
    <div class="sidebar__indicator"></div>
    <div class="sidebar__container">
        <nav class="sidebar__content">
            <ul class="sidebar__menu">
                @php
                    $menuItems = app(\Flute\Admin\AdminPanel::class)->getAllMenuItems();
                @endphp

                @foreach ($menuItems as $item)
                    <x-menu-item :item="$item" />
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
