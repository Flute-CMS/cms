@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', [
        'name' => __('admin.notifications.edit_title'),
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
            <h2>@t('admin.notifications.edit_title')</h2>
            <p>@t('admin.notifications.edit_description')</p>
        </div>
        <div>
            <button data-deleteaction="{{ $notification->id }}" data-deletepath="notifications" class="btn size-s error outline">
                @t('def.delete')
            </button>
        </div>
    </div>

    <form data-form="edit" data-page="notifications">
        @csrf
        <input type="hidden" name="id" value="{{ $notification->id }}">
        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="event_select">@t('admin.notifications.event')</label>
            </div>
            <div class="col-sm-9">
                <select name="event_select" id="event_select" class="form-control">
                    @foreach ($events as $key => $event)
                        <option value="{{ $key }}" @if ($notification->event == $key) selected @endif>
                            {{ __("admin.notifications.$event") }}
                        </option>
                    @endforeach
                    <option value="other" @if (!array_key_exists($notification->event, $events)) selected @endif>@t('admin.notifications.other')</option>
                </select>

                <input id="event_other" placeholder="@t('admin.notifications.specify_event')" type="text" class="form-control mt-2"
                    @if (!array_key_exists($notification->event, $events)) style="display:block;" 
                    value="{{ $notification->event }}" 
                @else 
                    hidden @endif>

                <input type="hidden" name="event" id="event" value="{{ $notification->event }}">
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
                    value="{{ $notification->title }}" required>
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
                    <div id="icon-output">{!! $notification->icon !!}</div>
                    <input name="icon" id="icon" placeholder="@t('admin.notifications.icon')" type="text"
                        class="form-control" value="{{ $notification->icon }}" required>
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
                <input name="url" id="url" placeholder="@t('admin.notifications.url')" type="text" class="form-control"
                    value="{{ $notification->url }}">
            </div>
        </div>

        <div class="position-relative row form-group">
            <div class="col-sm-3 col-form-label required">
                <label for="content">
                    @t('admin.notifications.content')
                </label>
            </div>
            <div class="col-sm-9">
                <textarea name="content" id="content" placeholder="@t('admin.notifications.content')" class="form-control" required>{{ $notification->content }}</textarea>
                <small>@t('admin.notifications.content_desc')</small>
            </div>
        </div>

        <div class="position-relative row form-group show" id="notification-result">
            <div class="col-sm-3 col-form-label required">
                <label for="content">
                    @t('def.preview')
                </label>
            </div>
            <div class="col-sm-9">
                <div class="notifications_item">
                    <div class="notifications_item_flex @if ($notification->url) with_link @endif">
                        {!! $notification->icon !!}
                        <div class="notifications_item_content">
                            <div class="notification_title">{!! $notification->title !!}</div>
                            <div class="notification_text">{!! $notification->content !!}</div>
                        </div>
                        <span class="ph ph-x icon-close" id="close-notification"></span>
                    </div>
                    @if ($notification->url)
                        <a class="notifications_item_link" href="{{ $notification->url }}"
                            target="_blank">@t('def.goto') <i class="ph ph-arrow-right"></i></a>
                    @endif
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
