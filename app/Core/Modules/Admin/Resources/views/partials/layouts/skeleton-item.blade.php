{{-- Renders a single skeleton descriptor. Receives $skel. --}}
@switch($skel['type'] ?? 'generic')
    @case('table')
        @php
            $skelCols = max($skel['columns'] ?? 3, 2);
            $skelRows = $skel['rows'] ?? 5;
        @endphp
        <div class="tabs-skeleton-card">
            @if (!empty($skel['title']))
                <div class="tabs-skeleton-card__header">
                    <div class="skeleton tabs-skeleton-card__icon"></div>
                    <div class="tabs-skeleton-card__title-group">
                        <div class="skeleton tabs-skeleton-card__title"></div>
                    </div>
                </div>
            @endif
            <div class="tabs-skeleton-card__content">
                @if (!empty($skel['searchable']))
                    <div class="tabs-skeleton-card__row">
                        <div class="tabs-skeleton-card__field">
                            <div class="skeleton tabs-skeleton-card__input" style="max-width:280px"></div>
                        </div>
                    </div>
                @endif
                <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                    @for ($si = 0; $si < $skelCols; $si++)
                        <div class="tabs-skeleton-card__field">
                            <div class="skeleton tabs-skeleton-card__label" style="width:{{ [80,100,120,90,110,70][$si % 6] }}px"></div>
                        </div>
                    @endfor
                </div>
                <div style="border-bottom:1px solid var(--transp-05);margin:0.25rem 0"></div>
                @for ($sr = 0; $sr < $skelRows; $sr++)
                    <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                        @for ($si = 0; $si < $skelCols; $si++)
                            <div class="tabs-skeleton-card__field">
                                <div class="skeleton tabs-skeleton-card__input" style="height:16px"></div>
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>
        </div>
        @break

    @case('sortable')
        @php $skelItems = $skel['items'] ?? 4; @endphp
        <div class="tabs-skeleton-card">
            @if (!empty($skel['title']))
                <div class="tabs-skeleton-card__header">
                    <div class="skeleton tabs-skeleton-card__icon"></div>
                    <div class="tabs-skeleton-card__title-group">
                        <div class="skeleton tabs-skeleton-card__title"></div>
                    </div>
                </div>
            @endif
            <div class="tabs-skeleton-card__content">
                @for ($si = 0; $si < $skelItems; $si++)
                    <div class="tabs-skeleton-card__row" style="padding:0.5rem 0{{ $si < $skelItems - 1 ? ';border-bottom:1px solid var(--transp-05)' : '' }}">
                        <div class="tabs-skeleton-card__field" style="flex:none;width:24px">
                            <div class="skeleton" style="width:16px;height:16px;border-radius:3px"></div>
                        </div>
                        <div class="tabs-skeleton-card__field">
                            <div class="skeleton tabs-skeleton-card__input" style="height:16px;width:{{ [60,75,50,65][$si % 4] }}%"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
        @break

    @case('rows')
        @php
            $skelFieldCount = max($skel['fields'] ?? 2, 1);
            $skelChunks = array_chunk(range(1, $skelFieldCount), 2);
        @endphp
        <div class="tabs-skeleton-card">
            @if (!empty($skel['title']))
                <div class="tabs-skeleton-card__header">
                    <div class="tabs-skeleton-card__title-group">
                        <div class="skeleton tabs-skeleton-card__title"></div>
                    </div>
                </div>
            @endif
            <div class="tabs-skeleton-card__content">
                @foreach ($skelChunks as $skelChunk)
                    <div class="tabs-skeleton-card__row {{ count($skelChunk) > 1 ? 'tabs-skeleton-card__row--split' : '' }}">
                        @foreach ($skelChunk as $__)
                            <div class="tabs-skeleton-card__field">
                                <div class="skeleton tabs-skeleton-card__label"></div>
                                <div class="skeleton tabs-skeleton-card__input"></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @break

    @case('field')
        <div class="tabs-skeleton-card__field">
            @if (!empty($skel['label']))
                <div class="skeleton tabs-skeleton-card__label"></div>
            @endif
            <div class="skeleton tabs-skeleton-card__input"></div>
        </div>
        @break

    @case('block')
        @if (!empty($skel['title']))
            <legend class="mt-2">
                <h5><div class="skeleton" style="height:16px;width:{{ max(120, mb_strlen($skel['title']) * 7) }}px;border-radius:4px;display:inline-block"></div></h5>
                @if (!empty($skel['description']))
                    <small class="d-block mb-1"><div class="skeleton" style="height:12px;width:{{ max(180, mb_strlen($skel['description']) * 5) }}px;border-radius:3px"></div></small>
                @endif
            </legend>
        @endif
        <div class="tabs-skeleton-card">
            <div class="tabs-skeleton-card__content">
                @if (!empty($skel['children']))
                    @foreach ($skel['children'] as $skelChild)
                        @include('admin::partials.layouts.skeleton-item', ['skel' => $skelChild])
                    @endforeach
                @else
                    <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                        <div class="tabs-skeleton-card__field">
                            <div class="skeleton tabs-skeleton-card__label"></div>
                            <div class="skeleton tabs-skeleton-card__input"></div>
                        </div>
                        <div class="tabs-skeleton-card__field">
                            <div class="skeleton tabs-skeleton-card__label"></div>
                            <div class="skeleton tabs-skeleton-card__input"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @break

    @case('columns')
        <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
            @foreach (($skel['columns'] ?? []) as $skelCol)
                @foreach ($skelCol as $skelChild)
                    @include('admin::partials.layouts.skeleton-item', ['skel' => $skelChild])
                @endforeach
            @endforeach
        </div>
        @break

    @case('split')
        @php $skelClasses = $skel['columnClass'] ?? ['col-md-6', 'col-md-6']; @endphp
        <div class="row g-4">
            @foreach (($skel['columns'] ?? []) as $skelIdx => $skelCol)
                <div class="{{ $skelClasses[$skelIdx] ?? 'col-md' }}">
                    @foreach ($skelCol as $skelChild)
                        @include('admin::partials.layouts.skeleton-item', ['skel' => $skelChild])
                    @endforeach
                </div>
            @endforeach
        </div>
        @break

    @case('chart')
        <div class="tabs-skeleton-card">
            @if (!empty($skel['title']))
                <div class="tabs-skeleton-card__header">
                    <div class="skeleton tabs-skeleton-card__icon"></div>
                    <div class="tabs-skeleton-card__title-group">
                        <div class="skeleton tabs-skeleton-card__title"></div>
                    </div>
                </div>
            @endif
            <div class="tabs-skeleton-card__content">
                <div class="skeleton" style="width:100%;height:{{ $skel['height'] ?? 320 }}px;border-radius:var(--border1)"></div>
            </div>
        </div>
        @break

    @case('metric')
        <div class="row g-3">
            @for ($si = 0; $si < ($skel['count'] ?? 3); $si++)
                <div class="col-md">
                    <div class="tabs-skeleton-card">
                        <div class="tabs-skeleton-card__header">
                            <div class="skeleton tabs-skeleton-card__icon"></div>
                            <div class="tabs-skeleton-card__title-group">
                                <div class="skeleton tabs-skeleton-card__title" style="width:60%"></div>
                                <div class="skeleton tabs-skeleton-card__subtitle" style="width:40%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>
        @break

    @case('blank')
        @if (!empty($skel['children']))
            @foreach ($skel['children'] as $skelChild)
                @include('admin::partials.layouts.skeleton-item', ['skel' => $skelChild])
            @endforeach
        @endif
        @break

    @case('tabs')
        {{-- Nested tabs skeleton: show tab headers + first tab's content --}}
        <div class="d-flex gap-2 mb-3 flex-wrap">
            @foreach (($skel['tabs'] ?? []) as $skelTabIdx => $skelTab)
                <div class="skeleton" style="height:34px;width:{{ max(60, mb_strlen($skelTab['title'] ?? '') * 8 + 24) }}px;border-radius:{{ !empty($skel['pills']) ? '20px' : '6px' }}"></div>
            @endforeach
        </div>
        @if (!empty($skel['tabs'][0]['children']))
            @foreach ($skel['tabs'][0]['children'] as $skelChild)
                @include('admin::partials.layouts.skeleton-item', ['skel' => $skelChild])
            @endforeach
        @endif
        @break

    @case('view')
        {{-- Custom view — show a generic content placeholder --}}
        <div class="tabs-skeleton-card">
            <div class="tabs-skeleton-card__content">
                <div class="tabs-skeleton-card__row">
                    <div class="tabs-skeleton-card__field">
                        <div class="skeleton" style="height:80px;width:100%;border-radius:var(--border1)"></div>
                    </div>
                </div>
            </div>
        </div>
        @break

    @default
        <div class="tabs-skeleton-card">
            <div class="tabs-skeleton-card__content">
                <div class="tabs-skeleton-card__row tabs-skeleton-card__row--split">
                    <div class="tabs-skeleton-card__field">
                        <div class="skeleton tabs-skeleton-card__label"></div>
                        <div class="skeleton tabs-skeleton-card__input"></div>
                    </div>
                    <div class="tabs-skeleton-card__field">
                        <div class="skeleton tabs-skeleton-card__label"></div>
                        <div class="skeleton tabs-skeleton-card__input"></div>
                    </div>
                </div>
            </div>
        </div>
@endswitch
