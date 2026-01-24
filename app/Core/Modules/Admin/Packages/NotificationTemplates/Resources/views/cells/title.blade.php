<div class="d-flex align-items-center">
    @if ($model->icon)
        <x-icon :name="$model->icon" class="me-2 text-muted" />
    @endif
    <span>{{ $model->title }}</span>
</div>
