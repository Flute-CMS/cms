@php
    $languages = config('lang.available');
@endphp

<div class="language-step mt-3">
    <div class="language-options">
        @foreach($languages as $code => $language)
            <div yoyo:post="setLanguage('{{ $language }}')"
                class="language-options__item {{ ($selectedLanguage === $language || ! $selectedLanguage && $language === $preferredLanguage) ? 'language-options__item--active' : '' }}">
                <img src="@asset('assets/img/langs/'.$language.'.svg')" alt="{{ $language }} flag" class="flag">
                <span class="name">{{ __('langs.'.$language) }}</span>
            </div>
        @endforeach
    </div>

    @if($selectedLanguage)
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 2]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="primary" yoyo:ignore>
            {{ __('install.common.next') }}
            <x-icon path="ph.regular.arrow-up-right" />
        </x-button>
    @endif
</div>