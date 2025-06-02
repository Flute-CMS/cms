@if ($sort)
<th @if ($width) style="width: {{ $width }}; @if ($style) {{ $style }} @endif" @elseif ($style) style="{{ $style }}"
@endif @if ($minWidth) style="min-width: {{ $minWidth }};" @endif class="text-{{ $align }} sortable-column"
        data-column="{{ $data_column ?? $slug ?? '' }}" @if (isset($aria_hidden)) aria-hidden="{{ $aria_hidden }}" @endif>
        <a href="{{ $sortUrl }}" hx-target="#main" hx-include="none" hx-swap="outerHTML" hx-boost="true" yoyo:ignore
            class="d-flex align-items-center text-decoration-none gap-2">
            {{ $title }}

            @php
                $currentSort = request()->input('sort', '');
                $isCurrentColumn = ltrim($currentSort, '-') === $column;
                $direction = str_starts_with($currentSort, '-') ? 'desc' : 'asc';
                
                $isDefaultSort = empty($currentSort) && isset($defaultSort) && $defaultSort;
                $isActiveSortColumn = $isCurrentColumn || $isDefaultSort;
                
                if ($isDefaultSort) {
                    $direction = $defaultSortDirection ?? 'asc';
                }
            @endphp

            @if ($isActiveSortColumn)
                <span class="sort-indicator">
                    @if ($direction === 'asc')
                        <x-icon path="ph.regular.sort-ascending" />
                    @else
                        <x-icon path="ph.regular.sort-descending" />
                    @endif
                </span>
            @else
                <span class="sort-indicator">
                    <x-icon path="ph.regular.funnel-simple" />
                </span>
            @endif
        </a>
    </th>
@else
<th @if ($width) style="width: {{ $width }}; @if ($style) {{ $style }} @endif" @elseif ($style) style="{{ $style }}"
@endif @if ($minWidth) style="min-width: {{ $minWidth }};" @endif class="text-{{ $align }}"
        data-column="{{ $data_column ?? $slug ?? '' }}" @if (isset($aria_hidden)) aria-hidden="{{ $aria_hidden }}" @endif>
        {{ $title }}
    </th>
@endif