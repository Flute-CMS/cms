<form>
    <x-forms.field>
        <x-forms.label for="display_mode">{{ __('widgets.settings.top_donors.display_mode') }}</x-forms.label>
        <x-fields.select name="display_mode" id="display_mode">
            <option value="podium" @if(($settings['display_mode'] ?? 'podium') == 'podium') selected @endif>
                {{ __('widgets.settings.top_donors.mode_podium') }}
            </option>
            <option value="list" @if(($settings['display_mode'] ?? 'podium') == 'list') selected @endif>
                {{ __('widgets.settings.top_donors.mode_list') }}
            </option>
            <option value="compact" @if(($settings['display_mode'] ?? 'podium') == 'compact') selected @endif>
                {{ __('widgets.settings.top_donors.mode_compact') }}
            </option>
        </x-fields.select>
        <x-fields.small>{{ __('widgets.settings.top_donors.display_mode_help') }}</x-fields.small>
    </x-forms.field>

    <x-forms.field class="mt-3">
        <x-forms.label for="limit">{{ __('widgets.settings.top_donors.limit') }}</x-forms.label>
        <x-fields.input type="number" name="limit" id="limit" value="{{ $settings['limit'] ?? 5 }}" min="1" max="20" />
        <x-fields.small>{{ __('widgets.settings.top_donors.limit_help') }}</x-fields.small>
    </x-forms.field>

    <x-forms.field class="mt-3">
        <x-fields.checkbox name="show_amount" id="show_amount" :checked="$settings['show_amount'] ?? true"
            label="{{ __('widgets.settings.top_donors.show_amount') }}">
        </x-fields.checkbox>
    </x-forms.field>
</form>
