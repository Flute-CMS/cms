@props([
    'buttons' => [],
])

<div class="buttons-editor" data-buttons-editor
    data-label-placeholder="{{ __('admin-notifications.button_fields.label') }}"
    data-url-placeholder="{{ __('admin-notifications.button_fields.url') }}"
    data-empty-text="{{ __('admin-notifications.buttons_empty') }}">
    <div class="buttons-editor__list" data-buttons-list>
        @forelse ($buttons as $index => $button)
            <div class="buttons-editor__item" data-button-index="{{ $index }}">
                <div class="buttons-editor__fields">
                    @include('admin::components.fields.input', [
                        'name' => 'button_' . $index . '_label',
                        'value' => $button['label'] ?? '',
                        'placeholder' => __('admin-notifications.button_fields.label'),
                    ])
                    @include('admin::components.fields.input', [
                        'name' => 'button_' . $index . '_url',
                        'value' => $button['url'] ?? '',
                        'placeholder' => __('admin-notifications.button_fields.url'),
                    ])
                </div>
                <button type="button" class="buttons-editor__remove" data-remove-button>
                    <x-icon path="ph.bold.x-bold" />
                </button>
            </div>
        @empty
            <div class="buttons-editor__empty" data-buttons-empty>
                {{ __('admin-notifications.buttons_empty') }}
            </div>
        @endforelse
    </div>

    <button type="button" class="buttons-editor__add" data-add-button>
        <x-icon path="ph.bold.plus-bold" />
        {{ __('admin-notifications.add_button') }}
    </button>
</div>
