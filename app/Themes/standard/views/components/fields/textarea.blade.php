@props(['name', 'value' => '', 'rows' => 4])

<div class="textarea-wrapper">
    <div class="textarea__field-container">
        <textarea name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" rows="{{ $rows }}"
            {{ $attributes->class(['textarea__field'])->merge(['class' => 'textarea__field']) }}>{{ $value }}</textarea>
    </div>

    @error($name)
        <span class="textarea__error">{{ $message }}</span>
    @enderror
</div>
