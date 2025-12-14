<header class="installer__header">
    <h1>{{ __('install.admin_user.heading') }}</h1>
    <p>{{ __('install.admin_user.subheading') }}</p>
</header>

<div class="admin-user-step">
    <div class="installer-content-container">
        <div class="admin-user-form">
            <div class="admin-user-form__section">
                <form id="admin-user-form" yoyo:post="createAdminUser" hx-trigger="submit">
                    <div class="form-group admin-form-icon-group">
                        <x-installer::input type="text" name="name" :label="__('install.admin_user.name')"
                            :value="$name" required />
                        <div class="admin-form-icon">
                            <x-icon path="ph.regular.user-circle" class="icon-input" />
                        </div>
                    </div>

                    <div class="form-group admin-form-icon-group">
                        <x-installer::input type="email" name="email" :label="__('install.admin_user.email')"
                            :value="$email" required />
                        <div class="admin-form-icon">
                            <x-icon path="ph.regular.envelope" class="icon-input" />
                        </div>
                    </div>

                    <div class="form-group admin-form-icon-group">
                        <x-installer::input type="text" name="login" :label="__('install.admin_user.login')"
                            :value="$login" required pattern="[A-Za-z0-9\-_\.]{6,20}" />
                        <div class="admin-form-icon">
                            <x-icon path="ph.regular.identification-badge" class="icon-input" />
                        </div>
                        <small class="text-muted">{{ __('install.admin_user.login_help') }}</small>
                    </div>

                    <div class="form-group-row">
                        <div class="form-group admin-form-icon-group">
                            <x-installer::input type="password" name="password"
                                :label="__('install.admin_user.password')" :value="$password" required />
                            <div class="admin-form-icon">
                                <x-icon path="ph.regular.lock-key" class="icon-input" />
                            </div>
                        </div>

                        <div class="form-group admin-form-icon-group">
                            <x-installer::input type="password" name="password_confirmation"
                                :label="__('install.admin_user.password_confirmation')"
                                :value="$password_confirmation" required />
                            <div class="admin-form-icon">
                                <x-icon path="ph.regular.check-circle" class="icon-input" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($errorMessage)
            <div class="alert mb-3 alert--danger">
                {{ $errorMessage }}
            </div>
        @endif
    </div>

    <div class="admin-user-form__actions">
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 4]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="secondary" yoyo:ignore>
            <x-icon path="ph.regular.arrow-left" />
            {{ __('install.common.back') }}
        </x-button>

        <x-button class="w-full" yoyo:post="createAdminUser"  variant="primary">
            {{ __('install.common.next') }}
            <x-icon path="ph.regular.arrow-up-right" />
        </x-button>
    </div>
</div>