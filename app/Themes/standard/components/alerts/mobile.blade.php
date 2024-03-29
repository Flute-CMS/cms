@push('header')
    @at(tt('assets/styles/components/alerts/mobile.scss'))
@endpush

@push('content')
    @if (!cookie()->has('mobile_alert') && is_installed() && user()->device()->isMobile())
        <div class="mobile_alert opened">
            <div class="mobile_alert-modal">
                <div class="mobile_alert-modal-header">
                    @t('def.alert')
                </div>
                <div class="mobile_alert-modal-content">
                    @t('def.site_not_support')
                </div>
                <button class="mobile_alert-modal-content-btn" id="mobile_close">
                    @t('def.i_dcare')
                </button>
            </div>
        </div>
    @endif
@endpush

@push('footer')
    @at(tt('assets/js/alerts/mobile.js'))
@endpush
