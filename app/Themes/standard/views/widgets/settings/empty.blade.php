<form>
    <x-forms.field>
        <x-forms.label for="height">{{ __('widgets.settings.empty.height') }}</x-forms.label>
        <x-fields.input type="number" name="height" id="height" value="{{ $settings['height'] ?? 100 }}" />
    </x-forms.field>
</form>