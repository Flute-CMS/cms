@push('header')
    @at(tt('assets/styles/pages/profile_edit/invoices.scss'))
@endpush

@push('profile_edit_content')
    <div class="card">
        <div class="card-header">
            @t('profile.invoices.info')
        </div>
        <div class="profile_settings">
            {!! $table !!}
        </div>
    </div>
@endpush