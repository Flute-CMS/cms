<div class="site-info-step">
    <div class="installer-content-container">
        <div class="tabs-minimal">
            <div class="tabs-minimal__nav">
                <button class="tab-minimal active" data-tab="basics">
                    {{ __('install.site_info.tab_basics') }}
                </button>
                <button class="tab-minimal" data-tab="seo">
                    {{ __('install.site_info.tab_seo') }}
                </button>
            </div>

            <div class="site-info-form">
                <form id="site-info-form" yoyo:post="saveSiteInfo" hx-trigger="submit">
                    <!-- Basic Info Tab -->
                    <div class="tab-minimal-content active" data-tab-content="basics">
                        <div class="form-group site-form-icon-group">
                            <x-installer::input type="url" name="url" :label="__('install.site_info.url')"
                                :value="$url" required id="site-url" />
                            <div class="site-form-icon">
                                <x-icon path="ph.regular.globe" class="icon-input" />
                            </div>
                            <small class="form-text">{{ __('install.site_info.url_help') }}</small>
                        </div>

                        <div class="form-group site-form-icon-group">
                            <x-installer::select name="timezone" :label="__('install.site_info.timezone')" 
                                :options="$timezones" :selected="$timezone" required />
                        </div>

                        <div class="site-form-icon-group">
                            <x-installer::textarea name="footer_description" :label="__('install.site_info.footer_description')"
                                :value="$footer_description" rows="2" />
                            <div class="site-form-icon site-form-icon--textarea">
                                <x-icon path="ph.regular.text-columns" class="icon-input" />
                            </div>
                            <small class="form-text">{{ __('install.site_info.footer_help') }}</small>
                        </div>
                    </div>

                    <!-- SEO Settings Tab -->
                    <div class="tab-minimal-content" data-tab-content="seo">
                        <div class="seo-preview">
                            <div class="seo-preview-label">
                                <span>{{ __('install.site_info.seo_preview') }}</span>
                                <div class="seo-preview-device">
                                    <x-icon path="ph.regular.desktop" class="seo-device-icon active" data-device="desktop" />
                                    <x-icon path="ph.regular.device-mobile" class="seo-device-icon" data-device="mobile" />
                                </div>
                            </div>
                            
                            <div class="seo-preview-card">
                                <div class="seo-preview-content">
                                    <div class="seo-preview-url" id="preview-url">{{ $url ?: 'https://' . $_SERVER['HTTP_HOST'] }}</div>
                                    <div class="seo-preview-title" id="preview-title">{{ $name ?: 'Your Website Title' }}</div>
                                    <div class="seo-preview-desc" id="preview-description">{{ $description ?: 'Your website description will appear here. Make it compelling to attract visitors.' }}</div>
                                </div>
                            </div>
                            
                            <div class="seo-tips">
                                <x-icon path="ph.regular.lightbulb" class="seo-tips-icon" />
                                <p>{{ __('install.site_info.seo_tips_content') }}</p>
                            </div>
                        </div>

                        <div class="seo-form">
                            <div class="form-group site-form-icon-group">
                                <x-installer::input type="text" name="name" :label="__('install.site_info.name')"
                                    :value="$name" required id="meta-title" />
                                <div class="site-form-icon">
                                    <x-icon path="ph.regular.buildings" class="icon-input" />
                                </div>
                                <div class="seo-character-counter" id="title-counter">0/60</div>
                            </div>

                            <div class="form-group site-form-icon-group">
                                <x-installer::textarea name="description" :label="__('install.site_info.description')"
                                    :value="$description" required rows="3" id="meta-description" />
                                <div class="site-form-icon site-form-icon--textarea">
                                    <x-icon path="ph.regular.article" class="icon-input" />
                                </div>
                                <div class="seo-character-counter" id="description-counter">0/160</div>
                            </div>

                            <div class="site-form-icon-group">
                                <x-installer::input type="text" name="keywords" :label="__('install.site_info.keywords')"
                                    :value="$keywords" required />
                                <div class="site-form-icon">
                                    <x-icon path="ph.regular.tag" class="icon-input" />
                                </div>
                                <small class="form-text">{{ __('install.site_info.keywords_help') }}</small>
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

    <div class="installer-form__actions">
        <x-button class="w-full" hx-get="{{ route('installer.step', ['id' => 5]) }}" hx-target="main" hx-push-url="true"
            hx-trigger="click" variant="secondary" yoyo:ignore>
            <x-icon path="ph.regular.arrow-left" />
            {{ __('install.common.back') }}
        </x-button>

        <x-button class="w-full" yoyo:post="saveSiteInfo" variant="primary">
            {{ __('install.common.next') }}
            <x-icon path="ph.regular.arrow-up-right" />
        </x-button>
    </div>
</div> 