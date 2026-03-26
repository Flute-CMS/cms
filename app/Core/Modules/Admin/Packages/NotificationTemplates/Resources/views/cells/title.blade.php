<div class="notification-title">
    <div class="notification-title__icon">
        <x-icon :path="$model->icon ?: 'ph.bold.bell-bold'" />
    </div>
    <div class="notification-title__text">
        <span class="notification-title__name">{{ $model->title }}</span>
        @if ($model->content)
            <span class="notification-title__preview">{{ \Flute\Core\Support\FluteStr::limit($model->content, 50) }}</span>
        @endif
    </div>
</div>
