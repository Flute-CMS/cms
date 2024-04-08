@push('header')
    @at(tt('assets/styles/pages/profile_edit/security.scss'))
@endpush

@push('profile_edit_content')
    <div class="card">
        <div class="card-header">
            @t('profile.security.info')
        </div>
        <div class="profile_settings">
            <div class="profile_settings_setting">
                <div class="profile_settings_setting_title">@t('profile.security.main_settings')</div>
                {!! $form->render() !!}
            </div>
        </div>
    </div>
@endpush

@push('footer')
    @at(tt('assets/js/pages/profile_edit/security.js'))
@endpush
