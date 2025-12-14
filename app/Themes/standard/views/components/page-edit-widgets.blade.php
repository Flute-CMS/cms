<aside class="page-edit-widgets">
    <div class="page-edit-widgets-categories">
        @foreach (app('widgets')->getWidgetsByCategory() as $category => $widgets)
            <div class="widget-category">
                <div class="widget-category-header" data-category="{{ $category }}">
                    <h6>{{ __("page.categories.$category") }}</h6>
                </div>
                <div class="widget-category-content">
                    <div class="page-edit-widgets-list">
                        @foreach ($widgets as $key => $widget)
                            <div class="page-edit-widgets-item grid-stack-item" data-widget-name="{{ $key }}"
                                data-gs-widget='@json(['w' => $widget->getDefaultWidth(), 'minW' => $widget->getMinWidth()])' draggable="true"
                                @if ($widget->hasSettings()) data-has-settings="1" @endif>
                                <div class="grid-stack-item-content">
                                    <x-icon :path="$widget->getIcon()" />
                                    <p>{{ __($widget->getName()) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button class="categories-scroll-left" aria-label="Scroll left">
        <x-icon path="ph.regular.caret-left" />
    </button>
    <button class="categories-scroll-right" aria-label="Scroll right">
        <x-icon path="ph.regular.caret-right" />
    </button>

    <div class="hidden">
        <div id="settings-widget-icon">
            <x-icon path="ph.regular.gear" />
        </div>
        <div id="delete-widget-icon">
            <x-icon path="ph.regular.trash" />
        </div>
        <div id="refresh-widget-icon">
            <x-icon path="ph.regular.arrows-clockwise" />
        </div>
    </div>
</aside>
