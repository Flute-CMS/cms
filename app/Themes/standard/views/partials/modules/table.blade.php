<div>
    <header class="table__header">
        <x-forms.field class="table__search" style="min-width: 300px" hx-trigger="input changed delay:500ms" yoyo
            yoyo:get="searchChanged">
            <x-fields.input name="search" id="search" placeholder="{{ __('def.lets_search') }}" :value="$search ?? ''" />
        </x-forms.field>
    </header>

    <div class="table-responsive">
        <table class="table">
            @if (!empty($columns))
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            @php
                                $isCurrentSort = $sortField === $column['field'];
                                $sortIcon = $isCurrentSort
                                    ? ($sortDirection === 'asc'
                                        ? 'ph.regular.sort-ascending'
                                        : 'ph.regular.sort-descending')
                                    : '';
                                $allowSort = $column['allowSort'] ?? true;
                                $align = $column['align'] ?? 'left';
                                $width = $column['width'] ?? false;
                            @endphp
                            <th @class(['table-header', 'sortable' => $allowSort, 'text-' . $align])
                                @if ($width) width="{{ $width }}" @endif
                                @if ($allowSort) yoyo:on="click" yoyo:get="sortBy('{{ $column['field'] }}')" @endif>
                                {{ $column['label'] }}
                                @if ($sortIcon && $allowSort)
                                    <span class="sort-icon"><x-icon :path="$sortIcon" /></span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            @php
                                $align = $column['align'] ?? 'left';
                                $width = $column['width'] ?? false;
                            @endphp

                            <td @class(['table-cell', 'text-' . $align])
                                @if ($width) width="{{ $width }}" @endif>
                                {!! $row[$column['field']] !!}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}">
                            <div class="py-4 text-center">
                                <h1 class="flex-center text-muted mb-1">
                                    <x-icon path="ph.regular.smiley-x-eyes" />
                                </h1>
                                <h3>
                                    {!! __('def.no_results_found') !!}
                                </h3>
                                <p class="text-muted flex-center text-balance text-center">
                                    {!! __('def.import_or_create') !!}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @php
        $totalPages = ceil($total / $perPage);
        $currentPage = $currentPage ?? 1;
    @endphp

    <footer class="table__footer d-flex flex-between">
        <div class="table__footer-per-page d-flex flex-center gap-2">
            <label for="perPage">
                <p class="text-muted">{{ __('def.records_per_page') }}:</p>
            </label>
            <x-fields.select name="perPage" id="perPage" yoyo>
                @foreach ($paginationOptions as $option)
                    <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </x-fields.select>
        </div>
        <ul class="table__pagination">
            <li class="table__pagination-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <button class="link-icon" yoyo:on="click" yoyo:get="setPage({{ max(1, $currentPage - 1) }})"
                    {{ $currentPage <= 1 ? 'disabled' : '' }}>
                    &lt;
                </button>
            </li>

            @if ($totalPages > 1)
                @if ($currentPage > 3)
                    <li class="table__pagination-item">
                        <button yoyo:on="click" yoyo:get="setPage(1)">
                            1
                        </button>
                    </li>
                    @if ($currentPage > 4)
                        <li class="table__pagination-item disabled">
                            <span class="text-ellipsis">...</span>
                        </li>
                    @endif
                @endif

                @for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                    <li class="table__pagination-item {{ $i == $currentPage ? 'active' : '' }}">
                        <button yoyo:on="click" yoyo:get="setPage({{ $i }})">
                            {{ $i }}
                        </button>
                    </li>
                @endfor

                @if ($currentPage < $totalPages - 2)
                    @if ($currentPage < $totalPages - 3)
                        <li class="table__pagination-item disabled">
                            <span class="text-ellipsis">...</span>
                        </li>
                    @endif
                    <li class="table__pagination-item">
                        <button yoyo:on="click" yoyo:get="setPage({{ $totalPages }})">
                            {{ $totalPages }}
                        </button>
                    </li>
                @endif
            @endif

            <li class="table__pagination-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                <button class="link-icon" yoyo:on="click" yoyo:get="setPage({{ min($currentPage + 1, $totalPages) }})"
                    {{ $currentPage >= $totalPages ? 'disabled' : '' }}>
                    &gt;
                </button>
            </li>
        </ul>
</div>
</div>
