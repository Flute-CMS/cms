<header class="admin-header">
    <div class="navbar">
        <div class="navbar__container">
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

                                <div class="navbar__profile-wrap">
                                    <button class="navbar__profile" data-admin-profile-toggle aria-expanded="false" aria-haspopup="true">
                                        <img src="{{ url(user()->getCurrentUser()->avatar) }}" alt="{{ user()->getCurrentUser()->name }}" class="navbar__profile-avatar">
                                    </button>

                                    <div class="admin-profile-dd" data-admin-profile-dropdown aria-hidden="true">
                                        <div class="admin-profile-dd__hero">
                                            <img src="{{ url(user()->getCurrentUser()->avatar) }}" alt="" class="admin-profile-dd__avatar">
                                            <div class="admin-profile-dd__info">
                                                <span class="admin-profile-dd__name">{!! user()->getCurrentUser()->getDisplayName() !!}</span>
                                                @if (user()->getCurrentUser()->login)
                                                    <span class="admin-profile-dd__sub">{{ '@' . user()->getCurrentUser()->login }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="admin-profile-dd__menu" hx-boost="false">
                                            <a href="{{ url('/profile/' . user()->getUrl()) }}" class="admin-profile-dd__item">
                                                <x-icon path="ph.regular.user-circle" />
                                                <span>{{ __('def.my_profile') }}</span>
                                            </a>
                                            <a href="{{ url('/') }}" class="admin-profile-dd__item">
                                                <x-icon path="ph.regular.house" />
                                                <span>{{ __('def.home') }}</span>
                                            </a>
                                        </div>

                                        <div class="admin-profile-dd__footer">
                                            <form action="{{ url('logout') }}" method="POST" hx-boost="false">
                                                @csrf
                                                <button type="submit" class="admin-profile-dd__item admin-profile-dd__item--danger">
                                                    <x-icon path="ph.regular.sign-out" />
                                                    <span>{{ __('def.logout') }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
