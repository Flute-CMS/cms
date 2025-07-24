<div class="chart-wrapper skeleton"
    style="min-height: {{ is_int($height) ? $height . 'px' : $height }}; min-width: {{ is_int($width) ? $width . 'px' : $width }}">
    <div id="{!! $id !!}" data-chart-options="{!! base64_encode(json_encode($chart->getChartOptions())) !!}"
        style="min-height: {{ is_int($height) ? $height . 'px' : $height }}; min-width: {{ is_int($width) ? $width . 'px' : $width }}">
    </div>
</div>
