@php
    $currentValue = $value ?? array_key_first($periods);
    $isCustomRange = $currentValue === 'custom';
@endphp

<div class="date-filter" data-date-filter>
    <div class="date-filter__row">
        @if (!empty($periods))
            <div class="date-filter__periods">
                <span class="date-filter__label">
                    <x-icon path="ph.regular.calendar" />
                    {{ __('admin-dashboard.labels.period') }}:
                </span>
                <x-fields.buttongroup
                    :name="$name"
                    :options="collect($periods)->map(fn($label) => ['label' => $label])->toArray()"
                    :value="$currentValue"
                    size="small"
                    color="primary"
                    :yoyo="$yoyo"
                />
            </div>
        @endif

        @if ($showCustomRange)
            <div class="date-filter__custom {{ !$isCustomRange ? 'date-filter__custom--hidden' : '' }}" data-custom-range>
                <div class="date-filter__range">
                    <x-fields.input
                        type="date"
                        :name="$name . '_from'"
                        :value="$dateFrom"
                        placeholder="{{ __('def.from') }}"
                        :yoyo="$yoyo"
                    />
                    <span class="date-filter__separator">—</span>
                    <x-fields.input
                        type="date"
                        :name="$name . '_to'"
                        :value="$dateTo"
                        placeholder="{{ __('def.to') }}"
                        :yoyo="$yoyo"
                    />
                </div>
            </div>
        @endif
    </div>

    @if (!empty($additionalFilters))
        <div class="date-filter__row date-filter__additional">
            @foreach ($additionalFilters as $filter)
                <div class="date-filter__filter">
                    <x-fields.select
                        :name="$filter['name']"
                        :label="$filter['label']"
                        :options="$filter['options']"
                        :value="$filter['value']"
                        :yoyo="$yoyo"
                    />
                </div>
            @endforeach
        </div>
    @endif
</div>
