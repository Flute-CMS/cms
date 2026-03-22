@php
    $iconFinder = app(\Flute\Core\Modules\Icons\Services\IconFinder::class);

    /**
     * Render SVG icon with proper size attributes.
     */
    function socialDriverIcon(\Flute\Core\Modules\Icons\Services\IconFinder $iconFinder, string $path, string $size = '1em'): string {
        $svg = $iconFinder->loadFile($path);
        if (!$svg) return '';

        return (string) (new \Flute\Core\Modules\Icons\Icon($svg))->setAttributes(collect([
            'width' => $size,
            'height' => $size,
            'fill' => 'currentColor',
            'style' => 'flex-shrink:0',
        ]));
    }
@endphp

<x-forms.field>
    <x-forms.label for="driverKey" required>
        {{ __('admin-social.fields.driver.label') }}
    </x-forms.label>

    @if ($isEditMode)
        <div class="input-wrapper">
            <div class="input__field-container input__field-container-readonly">
                @if (isset($driverIcons[$driverKey]))
                    <span class="input__prefix" style="width: 16px; height: 16px; font-size: 16px;">
                        {!! socialDriverIcon($iconFinder, $driverIcons[$driverKey]) !!}
                    </span>
                @endif
                <input type="text" value="{{ $driverKey }}" class="input__field" readonly />
            </div>
        </div>
    @else
        <div class="select-wrapper" yoyo hx-trigger="change delay:50ms">
            <div class="select__field-container" data-controller="select"
                data-select-placeholder="{{ __('admin-social.fields.driver.placeholder') }}"
                data-select-allow-empty="1"
                data-select-message-notfound="{{ __('No results found') }}">
                <select name="driverKey" id="driverKey" class="select__field" data-select required>
                    <option value="" @if (empty($driverKey)) selected @endif disabled>
                        {{ __('admin-social.fields.driver.placeholder') }}
                    </option>
                    @foreach ($availableDrivers as $key => $label)
                        @php
                            $iconPath = $driverIcons[$key] ?? null;
                            $iconSvg = $iconPath ? socialDriverIcon($iconFinder, $iconPath, '16px') : '';
                            $html = '<div class="d-flex align-items-center gap-2">' . $iconSvg . '<span>' . e($label) . '</span></div>';
                        @endphp
                        <option value="{{ $key }}"
                            @if ((string) $key === (string) $driverKey) selected @endif
                            data-html="{{ $html }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
</x-forms.field>
