@php
    $adminOnboardingCompleted = config('tips_complete.admin_onboarding.completed') ?? cookie()->has('admin_onboarding_completed');
@endphp

@if(! $adminOnboardingCompleted)
    <div id="admin-onboarding" class="admin-onboarding">
        <div class="admin-onboarding__backdrop"></div>
        <div class="admin-onboarding__container">
            <div class="admin-onboarding__sidebar">
                <div class="admin-onboarding__steps" id="onboarding-steps">
                    <div class="admin-onboarding__step active" data-step="0">
                        <div class="admin-onboarding__step-icon">
                            <x-icon path="ph.regular.sparkle" />
                        </div>
                        <div class="admin-onboarding__step-info">
                            <div class="admin-onboarding__step-title">{{ __('onboarding.new_design') }}</div>
                            <div class="admin-onboarding__step-subtitle">{{ __('onboarding.modern_interface') }}</div>
                        </div>
                    </div>
                    <div class="admin-onboarding__step" data-step="1">
                        <div class="admin-onboarding__step-icon">
                            <x-icon path="ph.regular.lightning" />
                        </div>
                        <div class="admin-onboarding__step-info">
                            <div class="admin-onboarding__step-title">{{ __('onboarding.dynamic_loading') }}</div>
                            <div class="admin-onboarding__step-subtitle">{{ __('onboarding.no_page_reloads') }}</div>
                        </div>
                    </div>
                    <div class="admin-onboarding__step" data-step="2">
                        <div class="admin-onboarding__step-icon">
                            <x-icon path="ph.regular.layout" />
                        </div>
                        <div class="admin-onboarding__step-info">
                            <div class="admin-onboarding__step-title">{{ __('onboarding.page_editor') }}</div>
                            <div class="admin-onboarding__step-subtitle">{{ __('onboarding.widget_system') }}</div>
                        </div>
                    </div>
                    <div class="admin-onboarding__step" data-step="3">
                        <div class="admin-onboarding__step-icon">
                            <x-icon path="ph.regular.paint-bucket" />
                        </div>
                        <div class="admin-onboarding__step-info">
                            <div class="admin-onboarding__step-title">{{ __('onboarding.dynamic_colors') }}</div>
                            <div class="admin-onboarding__step-subtitle">{{ __('onboarding.customize_appearance') }}</div>
                        </div>
                    </div>
                    <div class="admin-onboarding__step" data-step="4">
                        <div class="admin-onboarding__step-icon">
                            <x-icon path="ph.regular.sliders-horizontal" />
                        </div>
                        <div class="admin-onboarding__step-info">
                            <div class="admin-onboarding__step-title">{{ __('onboarding.improved_admin') }}</div>
                            <div class="admin-onboarding__step-subtitle">{{ __('onboarding.better_management') }}</div>
                        </div>
                    </div>
                    <div class="admin-onboarding__step" data-step="5">
                        <div class="admin-onboarding__step-icon">
                            <x-icon path="ph.regular.check-circle" />
                        </div>
                        <div class="admin-onboarding__step-info">
                            <div class="admin-onboarding__step-title">{{ __('onboarding.start_using') }}</div>
                            <div class="admin-onboarding__step-subtitle">{{ __('onboarding.get_started') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin-onboarding__content">
                <div class="admin-onboarding__content-header">
                    <div class="admin-onboarding__content-header-container">
                        <h4 id="slide-title">{{ __('onboarding.new_design') }}</h4>
                        <div class="admin-onboarding__nav">
                            <button class="admin-onboarding__nav-btn" id="onboarding-prev" disabled>
                                <x-icon path="ph.regular.caret-left" />
                            </button>
                            <span class="admin-onboarding__step-counter">
                                <span id="current-step">1</span>/<span id="total-steps">6</span>
                            </span>
                            <button class="admin-onboarding__nav-btn" id="onboarding-next">
                                <x-icon path="ph.regular.caret-right" />
                            </button>
                        </div>
                    </div>
                    <div class="admin-onboarding__progress">
                        <div class="admin-onboarding__progress-bar" id="onboarding-progress"></div>
                    </div>
                </div>
                <div class="admin-onboarding__slide-container" id="onboarding-slides">
                    <div class="admin-onboarding__slide active">
                        <div class="admin-onboarding__slide-image">
                            <img src="@asset('assets/img/onboarding/new-design.png')"
                                alt="{{ __('onboarding.new_design') }}">
                        </div>
                        <div class="admin-onboarding__slide-content">
                            <p>{{ __('onboarding.design_description_1') }}</p>
                            <p>{{ __('onboarding.design_description_2') }}</p>
                        </div>
                    </div>
                    <div class="admin-onboarding__slide">
                        <div class="admin-onboarding__slide-image">
                            <img src="@asset('assets/img/onboarding/dynamic.png')"
                                alt="{{ __('onboarding.dynamic_loading') }}">
                        </div>
                        <div class="admin-onboarding__slide-content">
                            <p>{{ __('onboarding.dynamic_description_1') }}</p>
                            <p>{{ __('onboarding.dynamic_description_2') }}</p>
                        </div>
                    </div>
                    <div class="admin-onboarding__slide">
                        <div class="admin-onboarding__slide-image">
                            <img src="@asset('assets/img/onboarding/page-editor.png')"
                                alt="{{ __('onboarding.page_editor') }}">
                        </div>
                        <div class="admin-onboarding__slide-content">
                            <p>{{ __('onboarding.editor_description_1') }}</p>
                            <p>{{ __('onboarding.editor_description_2') }}</p>
                        </div>
                    </div>
                    <div class="admin-onboarding__slide">
                        <div class="admin-onboarding__slide-image">
                            <img src="@asset('assets/img/onboarding/colors.png')"
                                alt="{{ __('onboarding.dynamic_colors') }}">
                        </div>
                        <div class="admin-onboarding__slide-content">
                            <p>{{ __('onboarding.colors_description_1') }}</p>
                            <p>{{ __('onboarding.colors_description_2') }}</p>
                        </div>
                    </div>
                    <div class="admin-onboarding__slide">
                        <div class="admin-onboarding__slide-image">
                            <img src="@asset('assets/img/onboarding/admin.png')"
                                alt="{{ __('onboarding.improved_admin') }}">
                        </div>
                        <div class="admin-onboarding__slide-content">
                            <p>{{ __('onboarding.admin_description_1') }}</p>
                            <p>{{ __('onboarding.admin_description_2') }}</p>
                        </div>
                    </div>
                    <div class="admin-onboarding__slide">
                        <div class="admin-onboarding__slide-image">
                            <img src="@asset('assets/img/onboarding/ready.png')" alt="{{ __('onboarding.start_using') }}">
                        </div>
                        <div class="admin-onboarding__slide-content">
                            <p>{{ __('onboarding.ready_description_1') }}</p>
                            <p>{{ __('onboarding.ready_description_2') }}</p>
                            <div class="admin-onboarding__action">
                                <x-button size="medium" type="button" class="admin-onboarding__start-btn"
                                    id="onboarding-complete">
                                    {{ __('onboarding.start_now') }}
                                    <x-icon path="ph.regular.arrow-up-right" />
                                </x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif