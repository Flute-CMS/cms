<script>
    let options_{!! $chart->id() !!} = {
        theme: {
            mode: '{!! $chart->theme() !!}'
        },
        chart: {
            type: '{!! $chart->type() !!}',
            height: {!! $chart->height() !!},
            width: '{!! $chart->width() !!}',
            toolbar: {!! $chart->toolbar() !!},
            zoom: {!! $chart->zoom() !!},
            fontFamily: "{!! $chart->fontFamily() !!}",
            foreColor: "{!! $chart->foreColor() !!}",
            sparkline: {!! $chart->sparkline() !!},
            background: "{!! $chart->background() !!}",
            @if ($chart->stacked())
                stacked: {!! $chart->stacked() !!},
            @endif
            dropShadow: {
                enabled: true,
                opacity: 0.1,
                blur: 5,
                left: -2,
                top: 5
            },
        },
        plotOptions: {
            bar: {!! $chart->horizontal() !!}
        },
        colors: {!! $chart->colors() !!},
        series: {!! $chart->dataset() !!},
        dataLabels: {!! $chart->dataLabels() !!},
        @if ($chart->labels())
            labels: {!! json_encode($chart->labels(), true) !!},
        @endif
        title: {
            text: "{!! $chart->title() !!}",
            offsetX: 20,
            style: {
                fontSize: '24px',
                fontWeight: '600',
                cssClass: 'apexcharts-yaxis-title'
            },
        },
        subtitle: {
            text: '{!! $chart->subtitle() !!}',
            align: '{!! $chart->subtitlePosition() !!}',
            offsetX: 20,
            style: {
                fontSize: '14px',
                cssClass: 'apexcharts-yaxis-title'
            }
        },
        xaxis: {
            categories: {!! $chart->xAxis() !!}
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'vertical',
                shadeIntensity: 0.5,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 0.8,
                stops: [0, 100]
            }
        },
        @if($chart->type() === 'pie' )
            stroke: {
                show: false,
            },
        @endif
        grid: {!! $chart->grid() !!},
        markers: {!! $chart->markers() !!},
        @if ($chart->stroke())
            stroke: {!! $chart->stroke() !!},
        @endif
        legend: {
            show: {!! $chart->showLegend() !!}
        },
    }

    $(function() {
        let el_chart = document.getElementById("{!! $chart->id() !!}");

        (async () => {
            let chart = new ApexCharts(el_chart,
                options_{!! $chart->id() !!});
            await chart.render();

            $(el_chart).parent().removeClass('skeleton');
        })()
    });
</script>
