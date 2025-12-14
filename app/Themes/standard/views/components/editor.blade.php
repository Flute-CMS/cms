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
    $inputId = $id ?? ($name ?? 'editor-' . uniqid());
    $hasError = $errors->has($name);
@endphp

@isset($label)
    <label for="{{ $inputId }}" class="form-label mb-2">{{ $label }}</label>
@endisset

<div @class(['markdown-editor-wrapper', 'is-invalid' => $hasError])>
    <textarea name="{{ $name }}" {{ $attributes }} id="{{ $inputId }}"
        data-editor="markdown" data-height="{{ $height ?? 300 }}"
        {!! isset($spellcheck) ? ' data-spellcheck="' . ($spellcheck ? 'true' : 'false') . '"' : '' !!}{!! isset($enableImageUpload) && $enableImageUpload ? ' data-upload="true"' : '' !!}{!! isset($imageUploadEndpoint) ? ' data-upload-url="' . $imageUploadEndpoint . '"' : '' !!}>{!! $value !!}</textarea>

    @error($name)
        <span class="input__error">{{ $message }}</span>
    @enderror
</div>

@isset($help)
    <div class="form-text text-muted">{{ $help }}</div>
@endisset
