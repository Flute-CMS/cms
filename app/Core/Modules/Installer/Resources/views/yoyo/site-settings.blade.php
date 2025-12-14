@php
    $robotsOptions = [
        'index, follow' => __('install.site_settings.robots_index_follow'),
        'noindex, nofollow' => __('install.site_settings.robots_noindex_nofollow'),
        'index, nofollow' => __('install.site_settings.robots_index_nofollow'),
        'noindex, follow' => __('install.site_settings.robots_noindex_follow'),
    ];
@endphp

<div class="site-settings-step">
    <div class="installer-content-container">
        <div class="tabs-minimal">
            <div class="tabs-minimal__nav">
                <button class="tab-minimal active" data-tab="general">
                    <x-icon path="ph.regular.sliders-horizontal" class="tab-icon" />
                    {{ __('install.site_settings.tab_general') }}
                </button>
                <button class="tab-minimal" data-tab="security">
                    <x-icon path="ph.regular.shield" class="tab-icon" />
                    {{ __('install.site_settings.tab_security') }}
                </button>
            </div>

            <div class="site-settings-form">
                <form id="site-settings-form" yoyo:action="saveSiteSettings">
                    <!-- General Settings Tab -->
                    <div class="tab-minimal-content active" data-tab-content="general">
                        <div class="settings-section">
                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.cron_mode') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.cron_mode_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="cron_mode" :checked="$cron_mode"
                                        yoyo:field="cron_mode" />
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.maintenance_mode') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.maintenance_mode_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="maintenance_mode" :checked="$maintenance_mode"
                                        yoyo:field="maintenance_mode" />
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.tips') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.tips_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="tips" :checked="$tips" yoyo:field="tips" />
                                </div>
                            </div>

                            <h3 class="settings-section-title mt-4">{{ __('install.site_settings.appearance_section') }}
                            </h3>

                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.share') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.share_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="share" :checked="$share" yoyo:field="share" />
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.flute_copyright') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.flute_copyright_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="flute_copyright" :checked="$flute_copyright"
                                        yoyo:field="flute_copyright" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings Tab -->
                    <div class="tab-minimal-content" data-tab-content="security">
                        <div class="settings-section">
                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.csrf_enabled') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.csrf_enabled_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="csrf_enabled" :checked="$csrf_enabled"
                                        yoyo:field="csrf_enabled" />
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-item__info">
                                    <div class="setting-item__label">
                                        {{ __('install.site_settings.convert_to_webp') }}
                                    </div>
                                    <div class="setting-item__desc">
                                        {{ __('install.site_settings.convert_to_webp_desc') }}
                                    </div>
                                </div>
                                <div class="setting-item__control">
                                    <x-installer::toggle name="convert_to_webp" :checked="$convert_to_webp"
                                        yoyo:field="convert_to_webp" />
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <div class="robots-select">
                                    <x-installer::select :label="__('install.site_settings.robots')" name="robots"
                                        :options="$robotsOptions" :selected="$robots" yoyo:field="robots"
                                        :options="$robotsOptions" />
                                </div>
                                <small class="form-text">{{ __('install.site_settings.robots_desc') }}</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($errorMessage)
            <div class="alert alert--danger">
                {{ $errorMessage }}
            </div>
        @endif
    </div>

    <div class="installer-form__actions">
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 6]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="secondary" yoyo:ignore>
            <x-icon path="ph.regular.arrow-left" />
            {{ __('install.common.back') }}
        </x-button>

        <x-button class="w-full" yoyo:post="saveSiteSettings" variant="primary">
            {{ __('install.common.finish') }}
            <x-icon path="ph.regular.check-circle" />
        </x-button>
    </div>
</div>