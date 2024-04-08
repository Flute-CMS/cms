@if (sizeof(config('lang.available')) > 1)
    @push('header')
        @at(tt('assets/styles/components/alerts/_lang.scss'))
    @endpush

    @push('toast-container')
        @if (!cookie()->has('current_lang') && is_installed())
            <div class="toast toast-lang show">
                <div class="toast-content-icon">
                    <img src="@at(tt('assets/img/lang.webp'))" alt="">
                </div>
                <i class="fa-solid fa-xmark icon-close" id="lang_close"></i>
                <div class="toast-flex">
                    <div class="toast-header">
                        {!! __('alerts.lang_message') !!}
                    </div>
                    <div class="toast-buttons">
                        <button class="choose_lang"
                            data-auth="{{ user()->isLoggedIn() ? 1 : 0 }}">{{ __('alerts.lang_change') }}</button>
                        <button class="choose_correct"
                            data-value="{{ app()->getLang() }}">{{ __('alerts.lang_correct') }}</button>
                    </div>
                </div>
            </div>
        @endif
    @endpush

    @push('footer')
        @at(tt('assets/js/alerts/lang.js'))
    @endpush
@endif
