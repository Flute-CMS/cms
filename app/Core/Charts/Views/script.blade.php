@push('head')
    {!! $chart->cdn() !!}
@endpush

<script>
    if (!window.fluteCharts) {
        window.fluteCharts = {};
        window.chartOptions = {};
        window.chartListenersAdded = false;
    }

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

    if (!window.chartListenersAdded) {
        const processCharts = (root = document) => {
            Object.keys(window.chartOptions).forEach(chartId => {
                const el = root.querySelector(`#${CSS.escape(chartId)}`);
                if (el && !el.querySelector('.apexcharts-canvas')) {
                    renderChart(el);
                }
            });
        };

        document.addEventListener('DOMContentLoaded', () => processCharts());

        document.addEventListener('htmx:afterSwap', (evt) => {
            const target = evt.detail?.target || evt.target;
            setTimeout(() => processCharts(target), 20);
        });

        window.chartListenersAdded = true;
    }

    function renderChart(el_chart) {
        if (!el_chart) return;

        let chartId = el_chart.id;
        let options = window.chartOptions[chartId];
        if (!options) return;

        if (window.fluteCharts[chartId]) {
            window.fluteCharts[chartId].destroy();
        }

        const start = () => {
            const chart = new ApexCharts(el_chart, options);
            chart.render().then(() => {
                el_chart.parentElement.classList.remove('skeleton');
                window.fluteCharts[chartId] = chart;
            }).catch(error => console.error('Chart render error:', error));
        };

        if (typeof ApexCharts === 'undefined') {
            waitForCDN(start);
        } else {
            start();
        }
    }

    if (!window.renderChart) {
        window.renderChart = renderChart;
    }

    if (!window.refreshCharts) {
        window.refreshCharts = function(root = document) {
            Object.keys(window.chartOptions || {}).forEach(function(chartId) {
                const el = root.querySelector(`#${CSS.escape(chartId)}`);
                if (el && !el.querySelector('.apexcharts-canvas')) {
                    renderChart(el);
                }
            });
        };
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
