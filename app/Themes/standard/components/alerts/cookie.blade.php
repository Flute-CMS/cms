@push('header')
    @at(tt('assets/styles/components/alerts/_cookie.scss'))
@endpush

@push('toast-container')
    @if(!cookie()->has('accept_cookie') && is_installed())
        <div class="toast toast-cookie show">
            <div class="toast-content-icon">
                <img src="@at(tt('assets/img/cookie.webp'))" alt="">
            </div>
            <i class="ph ph-x icon-close" id="cookie_close"></i>
            <div class="toast-flex">    
                <div class="toast-header">
                    {!! __('alerts.cookie_message') !!}
                </div>
                <div class="toast-text">
                    {!! __('alerts.cookie_text')!!}
                </div>
            </div>
        </div>
    @endif
@endpush

@push('footer')
    @at(tt('assets/js/alerts/cookie.js'))
@endpush