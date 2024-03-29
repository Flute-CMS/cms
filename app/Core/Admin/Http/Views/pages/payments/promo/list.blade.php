
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.payments.promo.list')]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/payments.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.payments.promo.header')</h2>
            <p>@t('admin.payments.promo.description')</p>
        </div>
        <div>
            <a href="{{url('admin/payments/promo/add')}}" class="btn size-s outline">
                @t('admin.payments.promo.add')
            </a>
        </div>
    </div>

    {!! $promo !!}
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/payments/promo/list.js')
@endpush
