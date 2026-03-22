@php
    $categoryIcons = [
        'system' => 'ph.regular.gear',
        'social' => 'ph.regular.share-network',
        'content' => 'ph.regular.article',
        'user' => 'ph.regular.user',
        'users' => 'ph.regular.users',
        'payments' => 'ph.regular.credit-card',
        'stats' => 'ph.regular.chart-bar',
        'admin' => 'ph.regular.shield',
        'media' => 'ph.regular.image',
        'general' => 'ph.regular.squares-four',
        'other' => 'ph.regular.dots-three',
    ];
    $allCategories = app('widgets')->getWidgetsByCategory();
    $totalWidgets = array_sum(array_map('count', $allCategories));
@endphp

<aside class="pe-dock" id="page-edit-sidebar">
    <div class="pe-dock__container">
        {{-- Search bar --}}
        <div class="pe-dock__header">
            <div class="pe-dock__search">
                <x-icon path="ph.regular.magnifying-glass" />
                <input type="text"
                       id="widget-search"
                       placeholder="{{ __('def.search') }}..."
                       autocomplete="off">
                <button class="pe-dock__search-clear" type="button" aria-label="Clear">
                    <x-icon path="ph.regular.x" />
                </button>
            </div>

            {{-- Horizontal category chips --}}
            <div class="pe-dock__chips">
                <button class="pe-dock__chip active"
                        type="button"
                        data-category="all">
                    <x-icon path="ph.regular.stack" />
                    <span>{{ __('def.all') }}</span>
                    <span class="pe-dock__chip-count">{{ $totalWidgets }}</span>
                </button>
                @foreach ($allCategories as $category => $widgets)
                    <button class="pe-dock__chip"
                            type="button"
                            data-category="{{ $category }}">
                        <x-icon :path="$categoryIcons[$category] ?? 'ph.regular.squares-four'" />
                        <span>{{ __("page.categories.$category") }}</span>
                        <span class="pe-dock__chip-count">{{ count($widgets) }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Widget area --}}
        <div class="pe-dock__body">
            <div class="pe-dock__content">
                {{-- "All" category — grouped by category with headers --}}
                <div class="pe-dock__category active" data-category="all">
                    <div class="pe-dock__widgets">
                        @foreach ($allCategories as $category => $widgets)
                            <div class="pe-dock__group" data-group="{{ $category }}">
                                <div class="pe-dock__group-title">
                                    <x-icon :path="$categoryIcons[$category] ?? 'ph.regular.squares-four'" />
                                    <span>{{ __("page.categories.$category") }}</span>
                                </div>
                                <div class="pe-dock__group-items">
                                    @foreach ($widgets as $key => $widget)
                                        @php $desc = method_exists($widget, 'getDescription') && $widget->getDescription() ? __($widget->getDescription()) : '' @endphp
                                        <div class="pe-widget-card grid-stack-item"
                                             data-widget-name="{{ $key }}"
                                             data-default-width="{{ $widget->getDefaultWidth() }}"
                                             data-tooltip="{{ $desc ?: __($widget->getName()) }}"
                                             data-tooltip-placement="top"
                                             @if ($widget->hasSettings()) data-has-settings="true" @endif
                                             gs-w="{{ $widget->getDefaultWidth() }}"
                                             gs-h="4">
                                            <div class="pe-widget-card__icon">
                                                <x-icon :path="$widget->getIcon()" />
                                            </div>
                                            <span class="pe-widget-card__name">{{ __($widget->getName()) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Individual category panels --}}
                @foreach ($allCategories as $category => $widgets)
                    <div class="pe-dock__category" data-category="{{ $category }}">
                        <div class="pe-dock__widgets">
                            @foreach ($widgets as $key => $widget)
                                <div class="pe-widget-card grid-stack-item"
                                     data-widget-name="{{ $key }}"
                                     data-default-width="{{ $widget->getDefaultWidth() }}"
                                     data-tooltip="{{ __($widget->getName()) }}"
                                     data-tooltip-placement="top"
                                     @if ($widget->hasSettings()) data-has-settings="true" @endif
                                     gs-w="{{ $widget->getDefaultWidth() }}"
                                     gs-h="4">
                                    <div class="pe-widget-card__icon">
                                        <x-icon :path="$widget->getIcon()" />
                                    </div>
                                    <span class="pe-widget-card__name">{{ __($widget->getName()) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Search results --}}
                <div class="pe-dock__search-results" style="display:none;">
                    <div class="pe-dock__widgets"></div>
                    <p class="pe-dock__no-results" style="display:none;">
                        <x-icon path="ph.regular.magnifying-glass" />
                        <span>{{ __('page-edit.no_widgets_found') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick inserter popover --}}
    <div class="pe-quick-inserter" id="pe-quick-inserter" style="display:none;">
        <div class="pe-quick-inserter__search">
            <x-icon path="ph.regular.magnifying-glass" />
            <input type="text" placeholder="{{ __('def.search') }}..." autocomplete="off" id="pe-quick-search">
        </div>
        <div class="pe-quick-inserter__list">
            @foreach ($allCategories as $category => $widgets)
                @foreach ($widgets as $key => $widget)
                    <button class="pe-quick-inserter__item"
                            type="button"
                            data-widget-name="{{ $key }}"
                            data-default-width="{{ $widget->getDefaultWidth() }}">
                        <x-icon :path="$widget->getIcon()" />
                        <span>{{ __($widget->getName()) }}</span>
                    </button>
                @endforeach
            @endforeach
        </div>
    </div>
</aside>

<div id="widget-toolbar-icons" hidden>
    <span data-icon="settings"><x-icon path="ph.regular.gear" /></span>
    <span data-icon="delete"><x-icon path="ph.regular.trash" /></span>
    <span data-icon="refresh"><x-icon path="ph.regular.arrows-clockwise" /></span>
    <span data-icon="drag"><x-icon path="ph.regular.dots-six-vertical" /></span>
    <span data-icon="excluded-paths"><x-icon path="ph.regular.eye-slash" /></span>
    <span data-icon="conditions"><x-icon path="ph.regular.funnel" /></span>
</div>
