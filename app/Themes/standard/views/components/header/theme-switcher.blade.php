@if(config('app.change_theme'))
    <li class="navbar__theme-switcher" data-tooltip="{{ __('def.change_theme') }}">
        <button id="theme-toggle" aria-label="{{ __('def.change_theme') }}"
            aria-pressed="{{ cookie()->get('theme', 'dark') === 'dark' ? 'true' : 'false' }}">
            <x-icon path="ph.regular.sun" class="sun-icon" @style(['display: none;' => cookie()->get('theme', 'dark') === 'dark']) aria-hidden="true" />
            <x-icon path="ph.regular.moon" class="moon-icon" @style(['display: none;' => cookie()->get('theme', 'dark') === 'light']) aria-hidden="true" />
        </button>
    </li>
@endif