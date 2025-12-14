<header class="admin-header">
    <div class="navbar">
        <div class="navbar__container">
            <button class="sidebar__toggle" @if (cookie()->get('admin-sidebar-collapsed', 'false') !== 'true') style="display: none" @endif>
                <x-icon path="ph.regular.sidebar-simple" />
            </button>
            <div class="navbar__content @if (cookie()->get('container-width', 'normal') === 'wide') container-wide @endif container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <button class="sidebar__toggle-mobile hamburger" aria-label="Toggle Sidebar">
                                <x-icon path="ph.regular.sidebar-simple" />
                            </button>

                            <div id="breadcrumb-container" class="d-flex align-content-center">
                                @include('admin::partials.breadcrumb')
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button class="navbar__customization" id="customization-trigger" aria-label="Customize"
                                    data-tooltip="{{ __('admin.customization') }}">
                                    <x-icon path="ph.regular.palette" />
                                </button>

                                <button class="navbar__search" id="search-trigger">
                                    <x-icon path="ph.bold.magnifying-glass-bold" />
                                    <span>{{ __('def.lets_search') }}</span>

                                    <kbd class="search-shortcut">Ctrl + K</kbd>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
