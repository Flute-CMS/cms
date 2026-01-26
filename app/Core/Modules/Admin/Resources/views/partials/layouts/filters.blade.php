@php
    $wrapperClass = $compact ? 'filters filters--compact' : 'filters';
@endphp

<div class="{{ $wrapperClass }}" data-filters>
    <div class="filters__row">
        @foreach ($filters as $filter)
            <div class="filters__item filters__item--{{ $filter['type'] }}">
                @switch($filter['type'])
                    @case('buttonGroup')
                        <x-fields.buttongroup :name="$filter['name']" :options="$filter['options']" :value="$filter['value']" :label="$filter['label']"
                            :default="$filter['default'] ?? 'all'" labelIcon="ph.regular.funnel" size="small" color="primary" :yoyo="$yoyo" />
                    @break

                    @case('select')
                        <x-fields.select :name="$filter['name']" :options="$filter['options']" :value="$filter['value']" :default="$filter['default'] ?? ''"
                            :allowEmpty="$filter['allowEmpty']" :yoyo="$yoyo" />
                    @break

                    @case('input')
                        <div class="filters__input-group">
                            @if ($filter['label'])
                                <label class="filters__input-label" for="{{ $filter['name'] }}">
                                    {{ $filter['label'] }}
                                </label>
                            @endif
                            <x-fields.input :type="$filter['inputType']" :name="$filter['name']" :value="$filter['value']" :default="$filter['default'] ?? ''"
                                :placeholder="$filter['placeholder']" :yoyo="$yoyo" />
                        </div>
                    @break

                    @case('dateRange')
                        <div class="filters__date-range">
                            <span class="filters__date-range-label">
                                <x-icon path="ph.regular.calendar" />
                                {{ $filter['label'] }}
                            </span>
                            <div class="filters__date-range-inputs">
                                <x-fields.input type="date" :name="$filter['name'] . '_from'" :value="$filter['valueFrom']" :default="$filter['defaultFrom'] ?? ''"
                                    placeholder="{{ __('def.from') }}" :yoyo="$yoyo" />
                                <span class="filters__date-range-separator">—</span>
                                <x-fields.input type="date" :name="$filter['name'] . '_to'" :value="$filter['valueTo']" :default="$filter['defaultTo'] ?? ''"
                                    placeholder="{{ __('def.to') }}" :yoyo="$yoyo" />
                            </div>
                        </div>
                    @break

                    @case('checkbox')
                        <label class="filters__checkbox">
                            <input type="checkbox" name="{{ $filter['name'] }}" value="1"
                                data-default="{{ $filter['default'] ? 'true' : 'false' }}"
                                {{ $filter['value'] ? 'checked' : '' }}
                                @if ($yoyo) yoyo hx-trigger="change" @endif />
                            <span class="filters__checkbox-label">{{ $filter['label'] }}</span>
                        </label>
                    @break
                @endswitch
            </div>
        @endforeach

        @if ($showReset && $hasActiveFilters)
            <div class="filters__item filters__item--reset">
                <button type="button" class="filters__reset-btn" data-filters-reset
                    data-tooltip="{{ __('admin.filters.reset') }}" data-tooltip-pos="top">
                    <x-icon path="ph.regular.x-circle" />
                    <span>{{ __('admin.filters.reset') }}</span>
                </button>
            </div>
        @endif
    </div>
</div>
