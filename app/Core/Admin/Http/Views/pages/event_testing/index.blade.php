@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('admin.event_testing.title')]),
])

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('admin.event_testing.title')</h2>
            <p>@t('admin.event_testing.description')</p>
        </div>
        <div>
            <button class="btn size-s outline" data-faq="@t('admin.what_it')" data-faq-content="@t('admin.event_testing.faq')">
                @t('admin.what_it')
            </button>
        </div>
    </div>

    <div class="position-relative row form-group">
        <div class="col-sm-3 col-form-label required">
            <label for="eventSelect">@t('admin.event_testing.event')</label>
            <small class="form-text text-muted">@t('admin.event_testing.event_desc')</small>
        </div>
        <div class="col-sm-9">
            <select id="eventSelect" class="form-control">
                <option value="" selected disabled>@t('admin.event_testing.select_event')</option>
            </select>
        </div>
    </div>

    <div id="eventParameters"></div>

    <div class="position-relative row form-check">
        <div class="col-sm-9 offset-sm-3">
            <button id="checkEvent" class="btn size-m btn--with-icon primary" disabled>
                @t('admin.event_testing.check')
                <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
            </button>
        </div>
    </div>
@endpush

@push('footer')
    <script>
        var events = {!! json_encode($events) !!};
        var currentUserId = {{ user()->id }};
    </script>

    @at('Core/Admin/Http/Views/assets/js/pages/event_testing/page.js')
@endpush
