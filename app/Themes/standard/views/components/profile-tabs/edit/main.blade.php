<form class="profile-settings-form" hx-swap="morph:outerHTML" id="profile-settings-form">
    <div class="profile-settings__section mb-4">
        <x-card id="user-settings">
            <div class="row gx-3 gy-3">
                <div class="col-md-6">
                    <x-forms.field>
                        <x-forms.label for="name" required>
                            {{ __('profile.edit.main.basic_information.fields.name') }}:
                        </x-forms.label>
                        <x-fields.input name="name" id="name" value="{{ $name ?? $user->name }}" required
                            placeholder="{{ __('profile.edit.main.basic_information.fields.name_placeholder') }}" />
                        <x-fields.small>
                            {{ __('profile.edit.main.basic_information.fields.name_info') }}
                        </x-fields.small>
                    </x-forms.field>
                </div>
                <div class="col-md-6">
                    <x-forms.field>
                        <x-forms.label for="login" required>
                            {{ __('profile.edit.main.basic_information.fields.login') }}:
                        </x-forms.label>
                        <x-fields.input name="login" id="login" value="{{ $login ?? $user->login }}"
                            placeholder="{{ __('profile.edit.main.basic_information.fields.login_placeholder') }}" />
                        <x-fields.small>
                            {{ __('profile.edit.main.basic_information.fields.login_info') }}
                        </x-fields.small>
                    </x-forms.field>
                </div>

                <div class="col-md-12">
                    <x-forms.field>
                        <x-forms.label for="email" required>
                            {{ __('profile.edit.main.basic_information.fields.email') }}:
                        </x-forms.label>
                        <x-fields.input type="email" name="email" id="email"
                            value="{{ $email ?? $user->email }}" required
                            placeholder="{{ __('profile.edit.main.basic_information.fields.email_placeholder') }}" />
                    </x-forms.field>
                </div>
                <div class="col-md-12">
                    <x-forms.field>
                        <x-forms.label for="uri">
                            {{ __('profile.edit.main.basic_information.fields.uri') }}:
                        </x-forms.label>
                        <x-fields.input prefix="@" name="uri" id="uri" value="{{ $uri ?? $user->uri }}"
                            placeholder="{{ __('profile.edit.main.basic_information.fields.uri_placeholder') }}" />
                        <x-fields.small>
                            {!! __('profile.edit.main.basic_information.fields.uri_info', [
                                ':example' => url('profile/') . '<b>test</b>',
                            ]) !!}
                        </x-fields.small>
                    </x-forms.field>
                </div>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveMain"
                        yoyo:on="click">
                        {{ __('profile.edit.main.basic_information.save_changes') }}
                    </x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </div>

    <div class="profile-settings__section mb-4" id="images-settings">
        <x-card>
            <x-slot:header>
                <h4>{{ __('profile.edit.main.profile_images.title') }}</h4>
                <p class="text-muted">{{ __('profile.edit.main.profile_images.description') }}</p>
            </x-slot:header>

            <div class="row">
                <div class="col-md-4">
                    <x-forms.field>
                        <x-forms.label for="avatar">
                            {{ __('profile.edit.main.profile_images.fields.avatar') }}:
                        </x-forms.label>
                        <x-fields.input accept="image/png, image/jpeg, image/gif, image/webp" type="file"
                            name="avatar" id="avatar"
                            defaultFile="{{ asset($user->avatar ?? config('profile.default_avatar')) }}" filePond=true
                            class="profile-settings__avatar" :filePondOptions="['stylePanelLayout' => 'compact circle']" />

                        @error('avatar')
                            <span class="input__error">{{ $message }}</span>
                        @enderror
                    </x-forms.field>
                </div>
                <div class="col-md-8">
                    <x-forms.field>
                        <x-forms.label for="banner">
                            {{ __('profile.edit.main.profile_images.fields.banner') }}:
                            <x-fields.input accept="image/png, image/jpeg, image/gif, image/webp" type="file"
                                name="banner" id="banner"
                                defaultFile="{{ asset($user->banner ?? config('profile.default_banner')) }}"
                                class="profile-settings__banner" filePond=true :filePondOptions="['stylePanelLayout' => 'compact']" />

                            @error('banner')
                                <span class="input__error">{{ $message }}</span>
                            @enderror
                        </x-forms.label>
                    </x-forms.field>
                </div>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveImages"
                        yoyo:on="click">
                        {{ __('def.save') }}
                    </x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </div>

    <div class="profile-settings__section mb-4" id="privacy-settings">
        <x-card>
            <x-slot:header>
                <h4>{{ __('profile.edit.main.profile_privacy.title') }}</h4>
                <p class="text-muted">{{ __('profile.edit.main.profile_privacy.description') }}</p>
            </x-slot:header>

            <div class="row">
                <div class="col-md-12">
                    <x-radio-group name="privacy">
                        <x-radio-group-item value="hidden"
                            label="{{ __('profile.edit.main.profile_privacy.fields.visible.label') }}"
                            icon="ph.regular.lock-key"
                            small="{{ __('profile.edit.main.profile_privacy.fields.visible.info') }}"
                            :checked="$user->hidden == true" />
                        <x-radio-group-item value="visible"
                            label="{{ __('profile.edit.main.profile_privacy.fields.hidden.label') }}"
                            icon="ph.regular.lock-key-open"
                            small="{{ __('profile.edit.main.profile_privacy.fields.hidden.info') }}"
                            :checked="$user->hidden == false" />
                    </x-radio-group>
                </div>
            </div>

            <x-slot:footer>
                <div class="profile-edit__card-footer">
                    <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="savePrivacy"
                        yoyo:on="click">
                        {{ __('def.save') }}
                    </x-button>
                </div>
            </x-slot:footer>
        </x-card>
    </div>

    @if ($user->login || $user->email)
        <div class="profile-settings__section mb-4" id="password-settings">
            <x-card>
                <x-slot:header>
                    <h4>{{ __('profile.edit.main.change_password.title') }}</h4>
                    <p class="text-muted mb-0">
                        {{ __('profile.edit.main.change_password.description') }}
                    </p>
                </x-slot:header>

                <div class="row gx-3 gy-3">
                    @if ($user->password)
                        <div class="col-md-12">
                            <x-forms.field>
                                <x-forms.label for="current_password" required>
                                    {{ __('profile.edit.main.change_password.fields.current_password') }}:
                                </x-forms.label>
                                <x-fields.input type="password" name="current_password" id="current_password"
                                    value="{{ $current_password ?? '' }}" required
                                    placeholder="{{ __('profile.edit.main.change_password.fields.current_password_placeholder') }}" />
                            </x-forms.field>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <x-forms.field>
                            <x-forms.label for="new_password" required>
                                {{ __('profile.edit.main.change_password.fields.new_password') }}:
                            </x-forms.label>
                            <x-fields.input type="password" name="new_password" id="new_password"
                                value="{{ $new_password ?? '' }}"
                                placeholder="{{ __('profile.edit.main.change_password.fields.new_password_placeholder') }}" />
                        </x-forms.field>
                    </div>
                    <div class="col-md-6">
                        <x-forms.field>
                            <x-forms.label for="new_password_confirmation" required>
                                {{ __('profile.edit.main.change_password.fields.confirm_new_password') }}:
                            </x-forms.label>
                            <x-fields.input type="password" name="new_password_confirmation"
                                id="new_password_confirmation" value="{{ $new_password_confirmation ?? '' }}"
                                placeholder="{{ __('profile.edit.main.change_password.fields.confirm_new_password_placeholder') }}" />
                        </x-forms.field>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="profile-edit__card-footer">
                        <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="savePassword"
                            yoyo:on="click">
                            {{ __('profile.edit.main.change_password.save_changes') }}
                        </x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </div>
    @endif

    @if (config('app.change_theme'))
        <div class="profile-settings__section mb-4" id="theme-settings">
            <x-card>
                <x-slot:header>
                    <h4>{{ __('profile.edit.main.profile_theme.title') }}</h4>
                    <p class="text-muted">{{ __('profile.edit.main.profile_theme.description') }}</p>
                </x-slot:header>

                <div class="row">
                    <div class="col-md-12">
                        <x-radio-group name="theme">
                            <x-radio-group-item icon="ph.regular.paint-brush" value="system"
                                small="{{ __('profile.edit.main.profile_theme.fields.system.info') }}"
                                :checked="cookie()->has('theme') === false"
                                label="{{ __('profile.edit.main.profile_theme.fields.system.label') }}" />
                            <x-radio-group-item icon="ph.regular.sun" value="light"
                                :checked="cookie()->get('theme') === 'light'"
                                small="{{ __('profile.edit.main.profile_theme.fields.light.info') }}"
                                label="{{ __('profile.edit.main.profile_theme.fields.light.label') }}" />
                            <x-radio-group-item icon="ph.regular.moon" value="dark"
                                small="{{ __('profile.edit.main.profile_theme.fields.dark.info') }}"
                                :checked="cookie()->get('theme') === 'dark'"
                                label="{{ __('profile.edit.main.profile_theme.fields.dark.label') }}" />
                        </x-radio-group>
                    </div>
                </div>

                <x-slot:footer>
                    <div class="profile-edit__card-footer">
                        <x-button type="primary" size="small" class="w-auto" withLoading yoyo:post="saveTheme"
                            yoyo:on="click">
                            {{ __('profile.edit.main.profile_theme.save_changes') }}
                        </x-button>
                    </div>
                </x-slot:footer>
            </x-card>
        </div>
    @endif

    @if (!empty($user->login))
        <div class="profile-settings__section mb-4">
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
</form>

@if (config('auth.two_factor.enabled'))
    @yoyo('profile-two-factor')
@endif
