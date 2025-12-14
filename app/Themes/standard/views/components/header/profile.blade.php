<li hx-boost="false">
    <button hx-get="{{ url('sidebar/miniprofile') }}" hx-target="#right-sidebar-content" class="navbar__profile"
        hx-swap="transition:false" aria-expanded="false" aria-label="{{ __('def.profile') }} {{ user()->name }}">
        <img data-profile-avatar src="{{ url(user()->avatar) }}" alt="{{ user()->name }}" loading="lazy" width="32"
            height="32">
    </button>
</li>
