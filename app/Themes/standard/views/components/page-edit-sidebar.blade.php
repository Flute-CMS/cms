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
    $firstCategory = array_key_first($allCategories);
@endphp

<aside class="pe-sidebar" id="page-edit-sidebar">
    {{-- Vertical category icon strip --}}
    <div class="pe-sidebar__icons">
        @foreach ($allCategories as $category => $widgets)
            <button class="pe-sidebar__icon-btn {{ $loop->first ? 'active' : '' }}"
                    type="button"
                    data-category="{{ $category }}"
                    data-tooltip="{{ __("page.categories.$category") }}"
                    data-tooltip-pos="right">
                <x-icon :path="$categoryIcons[$category] ?? 'ph.regular.squares-four'" />
            </button>
        @endforeach
    </div>

    {{-- Main panel --}}
    <div class="pe-sidebar__panel">
        {{-- Header: title + search --}}
        <div class="pe-sidebar__header">
            <div class="pe-sidebar__header-top">
                <h4 class="pe-sidebar__title">{{ __('page.widgets') }}</h4>
                <button class="pe-sidebar__close" type="button" id="pe-sidebar-close" aria-label="Close">
                    <x-icon path="ph.regular.x" />
                </button>
            </div>
            <div class="pe-sidebar__search">
                <x-icon path="ph.regular.magnifying-glass" />
                <input type="text"
                       id="widget-search"
                       placeholder="{{ __('def.search') }}..."
                       autocomplete="off">
                <button class="pe-sidebar__search-clear" type="button" aria-label="Clear">
                    <x-icon path="ph.regular.x" />
                </button>
            </div>
        </div>

        {{-- Scrollable widget list --}}
        <div class="pe-sidebar__content">
            @foreach ($allCategories as $category => $widgets)
                <div class="pe-sidebar__category {{ $loop->first ? 'active' : '' }}" data-category="{{ $category }}">
                    <div class="pe-sidebar__category-header">
                        <x-icon :path="$categoryIcons[$category] ?? 'ph.regular.squares-four'" />
                        <span>{{ __("page.categories.$category") }}</span>
                        <span class="pe-sidebar__count">{{ count($widgets) }}</span>
                    </div>
                    <div class="pe-sidebar__widgets">
                        @foreach ($widgets as $key => $widget)
                            <div class="pe-widget-card grid-stack-item"
                                 data-widget-name="{{ $key }}"
                                 data-default-width="{{ $widget->getDefaultWidth() }}"
                                 @if ($widget->hasSettings()) data-has-settings="true" @endif
                                 gs-w="{{ $widget->getDefaultWidth() }}"
                                 gs-h="1">
                                <div class="pe-widget-card__icon">
                                    <x-icon :path="$widget->getIcon()" />
                                </div>
                                <div class="pe-widget-card__info">
                                    <span class="pe-widget-card__name">{{ __($widget->getName()) }}</span>
                                </div>
                                <button class="pe-widget-card__add"
                                        type="button"
                                        data-tooltip="{{ __('page-edit.click_to_add') }}"
                                        data-tooltip-pos="left">
                                    <x-icon path="ph.regular.plus" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Search results (hidden by default) --}}
            <div class="pe-sidebar__search-results" style="display:none;">
                <p class="pe-sidebar__no-results" style="display:none;">
                    <x-icon path="ph.regular.magnifying-glass" />
                    <span>{{ __('page-edit.no_widgets_found') }}</span>
                </p>
            </div>
        </div>

        {{-- Footer tip --}}
        <div class="pe-sidebar__footer">
            <p class="pe-sidebar__tip">
                <x-icon path="ph.regular.hand-grabbing" />
                <span>{{ __('page.drag_widget_tip') }}</span>
            </p>
        </div>
    </div>

    {{-- Quick inserter popover (used by inline "+" buttons) --}}
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
</div>
