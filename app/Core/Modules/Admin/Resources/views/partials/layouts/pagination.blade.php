@if (!$compact || $paginator['totalPages'] > 1)
    <footer class="table__footer" hx-include="none">
        @if (!$compact)
            <div class="col-auto me-auto">
                @if (isset($columns) && \Flute\Admin\Platform\Fields\TD::isShowVisibleColumns($columns->toArray()))
                    <div class="btn-group dropup d-inline-block">
                        <x-link type="button" aria-haspopup="true" aria-expanded="false"
                            data-dropdown-open="dropdown-columns">
                            {{ __('def.configure_columns') }}
                        </x-link>
                        <div data-dropdown="dropdown-columns">
                            <ul class="table__columns" data-table-id="{{ $tableId ?? 'default' }}">
                                @foreach ($columns as $column)
                                    {!! $column->buildItemMenu() !!}
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <small class="text-muted d-block" style="font-size: 0.75rem">
                    {{ __('def.display_from_to', [
                        ':from' => ($paginator['currentPage'] - 1) * $paginator['perPage'] + 1,
                        ':to' => ($paginator['currentPage'] - 1) * $paginator['perPage'] + $paginator['totalPages'],
                        ':total' => $paginator['totalItems'],
                    ]) }}
                </small>
            </div>
        @endif

        @if ($paginator['totalPages'] > 1)
            <nav yoyo:ignore hx-target="#main">
                <ul class="table__pagination">
                    @if ($paginator['currentPage'] > 1)
                        <li class="table__pagination-item">
                            <a class="page-link link-icon"
                                href="{{ url()->withGet()->addParams(['page' => $paginator['currentPage'] - 1])->get() }}"
                                rel="prev" hx-swap="outerHTML" hx-boost="true" yoyo:ignore>
                                <x-icon path="ph.regular.caret-left" />
                            </a>
                        </li>
                    @else
                        <li class="table__pagination-item disabled">
                            <span class="page-link link-icon"><x-icon path="ph.regular.caret-left" /></span>
                        </li>
                    @endif

                    @for ($page = 1; $page <= $paginator['totalPages']; $page++)
                        @if (
                            $page == 1 ||
                                $page == $paginator['totalPages'] ||
                                ($page >= $paginator['currentPage'] - 1 && $page <= $paginator['currentPage'] + 1))
                            @if ($page == $paginator['currentPage'])
                                <li class="table__pagination-item active">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="table__pagination-item">
                                    <a class="page-link" yoyo:ignore
                                        href="{{ url()->withGet()->addParams(['page' => $page])->get() }}"
                                        hx-swap="outerHTML" hx-boost="true">{{ $page }}</a>
                                </li>
                            @endif
                        @elseif ($page == 2 || $page == $paginator['totalPages'] - 1)
                            <li class="table__pagination-item disabled"><span class="page-link">...</span></li>
                        @endif
                    @endfor

                    @if ($paginator['currentPage'] < $paginator['totalPages'])
                        <li class="table__pagination-item">
                            <a class="page-link link-icon" yoyo:ignore
                                href="{{ url()->withGet()->addParams(['page' => $paginator['currentPage'] + 1])->get() }}"
                                hx-swap="outerHTML" hx-boost="true" rel="next">
                                <x-icon path="ph.regular.caret-right" />
                            </a>
                        </li>
                    @else
                        <li class="table__pagination-item disabled">
                            <span class="page-link link-icon"><x-icon path="ph.regular.caret-right" /></span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </footer>
@endif
