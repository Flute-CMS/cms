@props(['type' => 'primary', 'size' => 'medium', 'icon' => null, 'href' => null])

<span class="badge {{ $type }} {{ $size }} {{ $icon ? 'badge-icon' : '' }}" {{ $attributes }}>
    @if($icon)
        <x-icon :path="$icon" />
    @endif

    {{ $slot }}
</span>