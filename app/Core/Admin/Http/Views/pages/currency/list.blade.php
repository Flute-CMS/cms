
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.currency.title')]),
])

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.currency.title')</h2>
            <p>@t('admin.currency.setting_description')</p>
        </div>
        <div>
            <a href="{{url('admin/currency/add')}}" class="btn size-s outline">
                @t('admin.currency.add')
            </a>
        </div>
    </div>

    {!! $table !!}
@endpush