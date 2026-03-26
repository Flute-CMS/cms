<section
    data-widget-id="{{ $widgetId }}"
    data-widget-name="Content"
    style="{{ $style }}"
>@if (!empty($wrapGrid))<div class="page-widgets">{!! $localContent !!}</div>@else{!! $localContent !!}@endif</section>
