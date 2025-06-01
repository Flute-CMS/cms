<form>
    <x-forms.field class="mb-3">
        <x-forms.label for="display_type">{{ __('widgets.settings.users.display_type') }}</x-forms.label>
        <x-fields.select name="display_type" id="display_type">
            <option value="text" {{ ($settings['display_type'] ?? 'text') == 'text' ? 'selected' : '' }}>
                {{ __('widgets.settings.users.display_text') }}</option>
            <option value="avatar" {{ ($settings['display_type'] ?? 'text') == 'avatar' ? 'selected' : '' }}>
                {{ __('widgets.settings.users.display_avatar') }}</option>
        </x-fields.select>
    </x-forms.field>
    <x-forms.field>
        <x-forms.label for="max_display">{{ __('widgets.settings.users.max_display') }}</x-forms.label>
        <x-fields.input type="number" name="max_display" id="max_display" value="{{ $settings['max_display'] ?? 10 }}"
            min="1" max="30" />
    </x-forms.field>
</form>