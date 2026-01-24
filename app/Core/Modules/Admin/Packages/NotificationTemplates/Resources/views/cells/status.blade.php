<div>
    @if ($model->is_enabled)
        <x-icon name="ph.check-circle" class="text-success" style="font-size: 1.25rem;" />
    @else
        <x-icon name="ph.x-circle" class="text-danger" style="font-size: 1.25rem;" />
    @endif
</div>
