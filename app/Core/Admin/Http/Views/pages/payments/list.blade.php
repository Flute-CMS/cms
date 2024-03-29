
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.payments.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/payments.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.payments.header')</h2>
            <p>@t('admin.payments.description')</p>
        </div>
        <div>
            <a href="{{url('admin/payments/add')}}" class="btn size-s outline">
                @t('admin.payments.add')
            </a>
        </div>
    </div>

    {!! $payments !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/payments/list.js')
@endpush
