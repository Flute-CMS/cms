@props([
    'label',
    'name',
    'id',
    'height',
    'spellcheck',
    'enableImageUpload',
    'imageUploadEndpoint',
    'value',
    'errors',
])

@php
    $hasError = $errors->has($name);
@endphp

@isset($label)
    <label for="{{ $id ?? ($name ?? 'editor-' . uniqid()) }}" class="form-label mb-2">{{ $label }}</label>
@endisset

<div @class(['markdown-editor-wrapper', 'is-invalid' => $hasError])>
    <textarea name="{{ $name }}" {{ $attributes }} id="{{ $id ?? ($name ?? 'editor-' . uniqid()) }}"
        data-editor="markdown" data-height="{{ $height ?? 300 }}"
        {!! isset($spellcheck) ? ' data-spellcheck="' . ($spellcheck ? 'true' : 'false') . '"' : '' !!}{!! isset($enableImageUpload) && $enableImageUpload ? ' data-upload="true"' : '' !!}{!! isset($imageUploadEndpoint) ? ' data-upload-url="' . $imageUploadEndpoint . '"' : '' !!}></textarea>

    @if (isset($value) && $value !== '')
        <script>
            (function() {
                var ta = document.getElementById('{{ $id ?? ($name ?? 'editor-' . uniqid()) }}');
                if (ta) {
                    ta.value = '{{ addslashes($value) }}';
                }
            })();
        </script>
    @endif

    @error($name)
        <span class="input__error">{{ $message }}</span>
    @enderror
</div>

@isset($help)
    <div class="form-text text-muted">{{ $help }}</div>
@endisset
