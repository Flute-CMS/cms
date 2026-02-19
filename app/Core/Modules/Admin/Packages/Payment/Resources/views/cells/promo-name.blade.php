@props(['name'])

<div class="flex items-center gap-2">
    <span class="text-sm font-medium">{{ $name }}</span>
    <button class="payment__copy-button" onclick="copyToClipboard({{ json_encode($name) }}); notyf.success({{ json_encode(__('def.copied')) }})">
        <x-icon path="ph.bold.copy-bold" />
    </button>
</div>
