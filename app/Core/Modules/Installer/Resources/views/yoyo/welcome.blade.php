<div class="welcome-step">
    <div class="welcome-step__button-container">
        <x-button hx-get="{{ route('installer.step', ['id' => 1]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="primary" class="welcome-step__button" yoyo:ignore>
            {{ __('install.welcome.get_started') }}
        </x-button>
        <x-icon path="ph.regular.arrow-up-right" class="welcome-step__button-icon" />
    </div>
    <div class="welcome-step__author">
        Made with ❤️ by <a href="https://github.com/flamesONE" target="_blank">Flames</a>
    </div>
</div>