<header class="installer__header">
    <h1>{{ __('install.database.heading') }}</h1>
    <p>{{ __('install.database.subheading') }}</p>
</header>

<div class="database-step">
    <div class="installer-content-container">
        <div class="database-form">
            <div class="database-form__section">
                <form yoyo:post="testConnection" hx-trigger="submit">
                    <div class="form-group">
                        <x-installer::select name="driver" :label="__('install.database.driver')" :options="$drivers"
                            :selected="request()->input('driver', $driver)" yoyo />
                    </div>

                    @if($driver !== 'sqlite')
                        <div class="form-group-row">
                            <div class="form-group">
                                <x-installer::input type="text" name="host" :label="__('install.database.host')"
                                    :value="$host" required />
                            </div>

                            <div class="form-group">
                                <x-installer::input type="text" name="port" :label="__('install.database.port')"
                                    :value="$port" required />
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <x-installer::input type="text" name="database" :label="__('install.database.database')"
                            :value="$database" required />
                        @if($driver === 'sqlite')
                            <small class="text-muted">{{ __('install.database.sqlite_note') }}</small>
                        @endif
                    </div>

                    @if($driver !== 'sqlite')
                        <div class="form-group-row">
                            <div class="form-group">
                                <x-installer::input type="text" name="username" :label="__('install.database.username')"
                                    :value="$username" required />
                            </div>

                            <div class="form-group">
                                <x-installer::input type="password" name="password" :label="__('install.database.password')"
                                    :value="$password" />
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <x-installer::input type="text" name="prefix" :label="__('install.database.prefix')"
                            :value="$prefix" />
                    </div>

                    <div class="form-actions">
                        <x-installer::button type="submit" variant="secondary">
                            <x-icon path="ph.regular.database" class="mr-2" />
                            {{ __('install.database.test_connection') }}
                        </x-installer::button>
                    </div>
                </form>
            </div>
        </div>

        @if ($errorMessage || $isConnected)
            <div class="alert mb-3 {{ $errorMessage ? 'alert--danger' : 'alert--success' }}">
                {{ $errorMessage ?: __('install.database.connection_success') }}
            </div>
        @endif
    </div>

    <div class="database-form__actions">
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 3]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="secondary" yoyo:ignore>
            <x-icon path="ph.regular.arrow-left" />
            {{ __('install.common.back') }}
        </x-button>

        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 5]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="primary" yoyo:ignore :disabled="!$isConnected">
            {{ __('install.common.next') }}
            <x-icon path="ph.regular.arrow-up-right" />
        </x-button>
    </div>
</div>