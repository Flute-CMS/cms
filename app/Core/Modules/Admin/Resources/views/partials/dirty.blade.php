@php
    $config = is_array($config ?? null) ? $config : [];

    $i18n = $config['i18n'] ?? [];
    $textBar = $i18n['bar'] ?? __('def.unsaved_changes');
    $textSave = $i18n['save'] ?? __('def.save');
    $textDiscard = $i18n['discard'] ?? __('def.reset');
    $textStay = $i18n['stay'] ?? __('def.cancel');

    $dialogTitle = $i18n['dialog_title'] ?? __('def.unsaved_changes');
    $dialogText = $i18n['dialog_text'] ?? __('def.unsaved_changes_text');
@endphp

{{-- Floating notification bar --}}
<div id="admin-dirty-bar" class="admin-dirty-bar" role="status" aria-live="polite">
    <span class="admin-dirty-bar__text">{{ $textBar }}</span>
    <div class="admin-dirty-bar__actions">
        <button type="button" class="btn btn-small btn-outline-primary" data-dirty-discard>
            {{ $textDiscard }}
        </button>
        <button type="button" class="btn btn-small btn-primary" data-dirty-save>
            {{ $textSave }}
        </button>
    </div>
</div>

{{-- Confirmation dialog --}}
<x-modal id="dirty-dialog" :title="$dialogTitle" :closeOnOverlay="false">
    <p class="text-center">
        {{ $dialogText }}
    </p>

    <x-slot:footer>
        <div class="d-flex justify-content-end align-items-center gap-3">
            <x-button type="outline-primary" class="w-100" data-dirty-stay>
                {{ $textStay }}
            </x-button>
            <x-button type="outline-primary" class="w-100" data-dirty-discard>
                {{ $textDiscard }}
            </x-button>
            <x-button type="primary" class="w-100" data-dirty-save>
                {{ $textSave }}
            </x-button>
        </div>
    </x-slot:footer>
</x-modal>
