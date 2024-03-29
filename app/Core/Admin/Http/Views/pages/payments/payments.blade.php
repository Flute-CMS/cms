@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.payments.payments')]),
])

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.payments.payments_header')</h2>
            <p>@t('admin.payments.payments_description')</p>
        </div>
    </div>

    {!! $payments !!}
@endpush