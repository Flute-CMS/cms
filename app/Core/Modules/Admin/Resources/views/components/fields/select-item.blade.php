@props(['value', 'label', 'selected' => false, 'attributes' => []])

<option value="{{ $value }}" {{ $selected ? 'selected' : '' }}
    @foreach ($attributes as $attr => $val)
        {{ $attr }}="{{ $val }}" @endforeach>
    {{ $label }}
</option>
