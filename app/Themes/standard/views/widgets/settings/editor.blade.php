<form>
    <x-forms.field class="mb-3">
        <x-fields.checkbox name="inCard" id="inCard" :checked="$settings['inCard'] ?? false"
            label="{{ __('widgets.settings.editor.inCard') }}">
        </x-fields.checkbox>
    </x-forms.field>

    <x-forms.field>
        <x-forms.label for="content">{{ __('widgets.settings.editor.content') }}</x-forms.label>
        <x-editor name="content" id="content-{{ uniqid() }}" height="300" value="{{ $settings['content'] ?? '' }}" />
    </x-forms.field>
</form>
