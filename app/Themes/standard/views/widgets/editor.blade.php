@if (empty($html))
    @t('widgets.editor_empty')
@else
    @if ($inCard)
        <div class="content-editor card md-content">
            <div class="card-body" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
                {!! $html !!}
            </div>
        </div>
    @else
        <div class="content-editor widget-editor md-content" hx-boost="true" hx-target="#main" hx-swap="outerHTML transition:true">
            {!! $html !!}
        </div>
    @endif
@endif
