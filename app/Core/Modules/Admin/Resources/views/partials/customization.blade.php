@php
    $currentTheme = cookie()->get('theme', 'dark');
    $containerWidth = cookie()->get('container-width', 'normal');

    $colorSchemes = [
        'default' => [
            'name' => __('admin.default'),
            'light' => [
                'primary' => '#0d0d0d',
                'accent' => '#76bd50',
            ],
            'dark' => [
                'primary' => '#F1F1F1',
                'accent' => '#A5FF75',
            ],
        ],
        'blue' => [
            'name' => __('admin.blue'),
            'light' => [
                'primary' => '#0A3880',
                'accent' => '#4285F4',
            ],
            'dark' => [
                'primary' => '#1565C0',
                'accent' => '#5E97F6',
            ],
        ],
        'purple' => [
            'name' => __('admin.purple'),
            'light' => [
                'primary' => '#4A148C',
                'accent' => '#9C27B0',
            ],
            'dark' => [
                'primary' => '#6A1B9A',
                'accent' => '#CE93D8',
            ],
        ],
        'orange' => [
            'name' => __('admin.orange'),
            'light' => [
                'primary' => '#E65100',
                'accent' => '#FF9800',
            ],
            'dark' => [
                'primary' => '#EF6C00',
                'accent' => '#FFAB40',
            ],
        ],
        'red' => [
            'name' => __('admin.red'),
            'light' => [
                'primary' => '#B71C1C',
                'accent' => '#F44336',
            ],
            'dark' => [
                'primary' => '#C62828',
                'accent' => '#EF5350',
            ],
        ],
    ];

    $currentColorScheme = cookie()->get('color-scheme', 'default');
@endphp

<aside class="modal right_sidebar" id="customization-modal" data-a11y-dialog="customization-modal" aria-hidden="true">
    <div class="right_sidebar__overlay" tabindex="-1" data-a11y-dialog-hide>
        <div class="right_sidebar__container" id="customization-sidebar-content" role="dialog" aria-modal="true"
            data-a11y-dialog-ignore-focus-trap>
            <header class="right_sidebar__header">
                <h5 class="right_sidebar__title">
                    <x-icon path="ph.regular.paint-brush" class="me-2" />
                    {{ __('admin.customization') }}
                </h5>
                <button class="right_sidebar__close" aria-label="Close modal" data-a11y-dialog-hide="right-sidebar"
                    data-original-tabindex="null"></button>
            </header>

            <div class="right_sidebar__content">
                <!-- Theme Mode Section -->
                <div class="customization-section">
                    <h6 class="customization-section__title">
                        <x-icon path="ph.regular.sun-dim" class="me-2" />
                        {{ __('admin.theme_mode') }}
                    </h6>

                    <div class="theme-toggle">
                        <button class="theme-toggle__btn {{ $currentTheme === 'light' ? 'active' : '' }}"
                            data-theme="light">
                            <x-icon path="ph.regular.sun" />
                            <span>{{ __('admin.light') }}</span>
                        </button>

                        <button class="theme-toggle__btn {{ $currentTheme === 'dark' ? 'active' : '' }}"
                            data-theme="dark">
                            <x-icon path="ph.regular.moon" />
                            <span>{{ __('admin.dark') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Color Scheme Section -->
                <div class="customization-section">
                    <h6 class="customization-section__title">
                        <x-icon path="ph.regular.palette" class="me-2" />
                        {{ __('admin.color_scheme') }}
                    </h6>

                    <div class="color-schemes">
                        @foreach ($colorSchemes as $id => $scheme)
                            <button class="color-scheme__item {{ $currentColorScheme === $id ? 'active' : '' }}"
                                data-color-scheme="{{ $id }}">
                                <div class="color-scheme__preview">
                                    <div class="color-scheme__colors">
                                        <div class="color-scheme__primary"
                                            style="background-color: {{ $scheme[$currentTheme]['primary'] }}"></div>
                                        <div class="color-scheme__accent"
                                            style="background-color: {{ $scheme[$currentTheme]['accent'] }}"></div>
                                    </div>
                                </div>
                                <span class="color-scheme__name">{{ $scheme['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Container Width Section -->
                <div class="customization-section">
                    <h6 class="customization-section__title">
                        <x-icon path="ph.regular.arrows-horizontal" class="me-2" />
                        {{ __('admin.container_width') }}
                    </h6>

                    <div class="container-width">
                        <button class="container-width__btn {{ $containerWidth === 'normal' ? 'active' : '' }}"
                            data-container-width="normal">
                            <div class="container-width__preview container-width__preview--normal">
                                <div class="container-width__bar"></div>
                            </div>
                            <span>{{ __('admin.normal') }}</span>
                        </button>

                        <button class="container-width__btn {{ $containerWidth === 'wide' ? 'active' : '' }}"
                            data-container-width="wide">
                            <div class="container-width__preview container-width__preview--wide">
                                <div class="container-width__bar"></div>
                            </div>
                            <span>{{ __('admin.wide') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="right_sidebar__footer">
                <x-button type="outline-primary" class="w-100" data-a11y-dialog-hide="right-sidebar"
                    data-original-tabindex="null">
                    {{ __('def.close') }}
                </x-button>
            </div>
        </div>
    </div>
</aside>
