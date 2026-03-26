@props([
    'variables' => [],
])

@if (!empty($variables))
    <div class="variables-list">
        <div class="variables-list__header">
            <x-icon path="ph.bold.brackets-curly-bold" />
            <span>{{ __('admin-notifications.blocks.variables') }}</span>
        </div>
        @foreach ($variables as $name => $description)
            <button type="button" class="variables-list__item"
                data-variable="{{ $name }}"
                data-tooltip="{{ $description }}"
                data-tooltip-pos="top">
                {!! '{' . e($name) . '}' !!}
            </button>
        @endforeach
    </div>
@endif
