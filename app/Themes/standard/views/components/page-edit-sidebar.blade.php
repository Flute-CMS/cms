<aside class="page-edit-sidebar" id="page-edit-sidebar">
    <div class="page-edit-sidebar__backdrop"></div>

    <div class="page-edit-sidebar__container">
        <div class="page-edit-sidebar__header">
            <h3>{{ __('page.widgets') }}</h3>
            <div class="page-edit-sidebar__search">
                <x-icon path="ph.regular.magnifying-glass" />
                <input type="text"
                       id="widget-search"
                       placeholder="{{ __('def.search') }}..."
                       autocomplete="off">
                <button class="page-edit-sidebar__search-clear" type="button" aria-label="Clear">
                    <x-icon path="ph.regular.x" />
                </button>
            </div>
        </div>

        <div class="page-edit-sidebar__categories">
            @foreach (app('widgets')->getWidgetsByCategory() as $category => $widgets)
                <div class="sidebar-category" data-category="{{ $category }}">
                    <button class="sidebar-category-header" type="button">
                        <span class="sidebar-category-header__title">
                            @php
                                $icons = [
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
                            @endphp
                            <x-icon :path="$icons[$category] ?? 'ph.regular.squares-four'" />
                            {{ __("page.categories.$category") }}
                        </span>
                        <span class="sidebar-category-header__meta">
                            <span class="sidebar-category-count">{{ count($widgets) }}</span>
                            <x-icon path="ph.regular.caret-down" class="sidebar-category-arrow" />
                        </span>
                    </button>
                    <div class="sidebar-category-content">
                        <div class="sidebar-category-widgets">
                            @foreach ($widgets as $key => $widget)
                                <div class="widget-item"
                                     data-widget-name="{{ $key }}"
                                     data-default-width="{{ $widget->getDefaultWidth() }}"
                                     @if ($widget->hasSettings()) data-has-settings="true" @endif
                                     draggable="true">
                                    <div class="widget-item__content">
                                        <div class="widget-item__icon">
                                            <x-icon :path="$widget->getIcon()" />
                                        </div>
                                        <div class="widget-item__info">
                                            <span class="widget-item__name">{{ __($widget->getName()) }}</span>
                                        </div>
                                        <div class="widget-item__drag">
                                            <x-icon path="ph.regular.dots-six-vertical" />
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="page-edit-sidebar__footer">
            <p class="page-edit-sidebar__tip">
                <x-icon path="ph.regular.info" />
                <span>{{ __('page.drag_widget_tip') }}</span>
            </p>
        </div>
    </div>

    <template id="widget-toolbar-icons">
        <span data-icon="settings"><x-icon path="ph.regular.gear" /></span>
        <span data-icon="delete"><x-icon path="ph.regular.trash" /></span>
        <span data-icon="refresh"><x-icon path="ph.regular.arrows-clockwise" /></span>
    </template>
</aside>
