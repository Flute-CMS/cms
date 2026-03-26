@if (!empty($user->login))
    <div class="profile-settings__section profile-settings__section--danger">
        <x-card>
            <x-slot:header>
                <h4>{{ __('profile.edit.main.delete_account.title') }}</h4>
                <p class="text-muted mb-0">
                    {{ __('profile.edit.main.delete_account.description') }}
                </p>
            </x-slot:header>

            <x-forms.field>
                <x-forms.label for="delete_confirmation" required>
                    {{ __('profile.edit.main.delete_account.fields.confirmation') }}:
                </x-forms.label>
                <x-fields.input type="text" name="delete_confirmation" id="delete_confirmation"
                    value="{{ $delete_confirmation ?? '' }}"
                    placeholder="{{ __('profile.edit.main.delete_account.fields.confirmation_placeholder') }}" />
            </x-forms.field>

            <x-slot:footer>
                <x-button type="error" size="small" class="w-auto" withLoading yoyo:post="deleteAccount"
                    yoyo:on="click">
                    {{ __('profile.edit.main.delete_account.delete_button') }}
                </x-button>
            </x-slot:footer>
        </x-card>
    </div>
@endif
