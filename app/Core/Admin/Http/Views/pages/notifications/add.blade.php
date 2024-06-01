@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.notifications.add_title'),
    ]),
])

@push('header')
    @at('Core/Admin/Http/Views/assets/styles/pages/notifications.scss')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <a class="back-btn" href="{{ url('admin/notifications/list') }}">
                <i class="ph ph-arrow-left ignore"></i>
                @t('def.back')
            </a>
            <h2>@t('admin.notifications.add_title')</h2>
            <p>@t('admin.notifications.add_description')</p>
        </div>
    </div>

    <form data-form="add" data-page="notifications">
        @csrf
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="event_select">@t('admin.notifications.event')</label>
            </div>
            <div class="col-sm-9">
                <select name="event_select" id="event_select" class="form-control">
                    @foreach ($events as $key => $event)
                        <option value="{{ $key }}">{{ __("admin.notifications.$event") }}</option>
                    @endforeach
                    <option value="other">@t('admin.notifications.other')</option>
                </select>

                <input id="event_other" placeholder="@t('admin.notifications.specify_event')" type="text" class="form-control mt-2" hidden>

                <input type="hidden" name="event" id="event" value="">
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="title">
                    @t('admin.notifications.title_label')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="title" id="title" placeholder="@t('admin.notifications.title_label')" type="text" class="form-control"
                    required>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="icon">
                    @t('admin.notifications.icon')
                </label>
                <small>@t('admin.notifications.icon_desc')</small>
            </div>
            <div class="col-sm-9">
                <div class="d-flex align-items-center">
                    <div id="icon-output"></div>
                    <input name="icon" id="icon" placeholder="@t('admin.notifications.icon')" type="text"
                        class="form-control" required>
                </div>
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label">
                <label for="url">
                    @t('admin.notifications.url')
                </label>
            </div>
            <div class="col-sm-9">
                <input name="url" id="url" placeholder="@t('admin.notifications.url')" type="text" class="form-control">
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="content">
                    @t('admin.notifications.content')
                </label>
            </div>
            <div class="col-sm-9">
                <textarea name="content" id="content" placeholder="@t('admin.notifications.content')" class="form-control" required></textarea>
                <small>@t('admin.notifications.content_desc')</small>
            </div>
        </div>

        <div class="position-relative row form-group" id="notification-result">
            <div class="col-sm-3 col-form-label required">
                <label for="content">
                    @t('def.preview')
                </label>
            </div>
            <div class="col-sm-9">
                <div class="notifications_item">
                    <div class="notifications_item_flex">
                        <i class="ph"></i>
                        <div class="notifications_item_content">
                            <div class="notification_title"></div>
                            <div class="notification_text"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопка отправки -->
        <div class="position-relative row form-check">
            <div class="col-sm-9 offset-sm-3">
                <button type="submit" data-save class="btn size-m btn--with-icon primary">
                    @t('def.save')
                    <span class="btn__icon arrow"><i class="ph ph-arrow-right"></i></span>
                </button>
            </div>
        </div>
    </form>
@endpush

@push('footer')
    @at('Core/Admin/Http/Views/assets/js/pages/notifications/add.js')
@endpush
