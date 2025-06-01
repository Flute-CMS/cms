@push('head')
    {!! $chart->cdn() !!}
@endpush

<script>
    window.chartOptions = window.chartOptions || {};
    window.chartOptions["{!! $chart->id() !!}"] = {
        theme: {
            mode: '{!! $chart->theme() !!}'
        },
        chart: {
            type: '{!! $chart->type() !!}',
            height: {!! $chart->height() !!},
            width: '{!! $chart->width() !!}',
            toolbar: {!! $chart->toolbar() !!},
            zoom: {!! $chart->zoom() !!},
            sparkline: {!! $chart->sparkline() !!},
            background: "{!! $chart->background() !!}",
            @if ($chart->stacked())
                stacked: {!! $chart->stacked() !!},
            @endif
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
        @if ($chart->type() === 'pie')
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
    };

    document.addEventListener('DOMContentLoaded', function() {
        let el_chart = document.getElementById("{!! $chart->id() !!}");
        waitForCDN(() => renderChart(el_chart));
    });

    document.addEventListener('htmx:afterSwap', function(evt) {
        let el_chart = document.getElementById("{!! $chart->id() !!}");
        if (el_chart && !el_chart.querySelector('.apexcharts-canvas')) {
            waitForCDN(() => renderChart(el_chart));
        }
    });

    async function renderChart(el_chart) {
        if (!el_chart) return;

        let options = window.chartOptions[el_chart.id];
        if (!options) return;

        let chart = new ApexCharts(el_chart, options);
        console.log(options, el_chart.id);
        await chart.render();
        el_chart.parentElement.classList.remove('skeleton');
    }

    function waitForCDN(callback) {
        const interval = setInterval(() => {
            if (typeof ApexCharts !== 'undefined') {
                clearInterval(interval);
                callback();
            }
        }, 100);
    }
</script>
