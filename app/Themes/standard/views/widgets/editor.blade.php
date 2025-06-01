@if (empty($html))
    @t('widgets.editor_empty')
@else
    @if ($inCard)
        <div class="content-editor card">
            <div class="card-body">
                {!! $html !!}
            </div>
        </div>
    @else
        <div class="content-editor widget-editor">
            {!! $html !!}
        </div>
    @endif
@endif
