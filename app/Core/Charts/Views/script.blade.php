@once
@push('head')
    {!! $chart->cdn() !!}
@endpush

<script>
    if (!window.chartManager) {
        const chartManager = {
            fluteCharts: {},
            chartRendering: {},

            renderChart(el, options) {
                const chartId = el.id;
                if (this.chartRendering[chartId]) return;
                this.chartRendering[chartId] = true;

                if (this.fluteCharts[chartId]) {
                    try {
                        this.fluteCharts[chartId].destroy();
                    } catch (e) {
                        console.warn(`Could not destroy chart #${chartId}. It might have been already removed.`, e);
                    }
                }

                const start = () => {
                    try {
                        const styles = getComputedStyle(document.documentElement);
                        const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
                        const textColor = styles.getPropertyValue('--text')?.trim() || (isDark ? '#fff' : '#1a1a1a');
                        const textMuted = styles.getPropertyValue('--text-400')?.trim() || (isDark ? '#888' : '#666');
                        const gridColor = styles.getPropertyValue('--transp-05')?.trim() || (isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)');
                        
                        const chartType = options.chart?.type || 'line';
                        const isAreaOrLine = ['area', 'line'].includes(chartType);
                        const isDonutOrPie = ['donut', 'pie', 'radialBar'].includes(chartType);
                        
                        const themedOptions = {
                            ...options,
                            chart: {
                                ...options.chart,
                                foreColor: textColor,
                                fontFamily: 'SF Pro Display, Manrope, Inter, system-ui, -apple-system, sans-serif',
                                background: 'transparent',
                                toolbar: { show: false, ...(options.chart?.toolbar || {}) },
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 600,
                                    animateGradually: { enabled: true, delay: 100 },
                                    dynamicAnimation: { enabled: true, speed: 300 },
                                },
                                dropShadow: isAreaOrLine ? { enabled: true, top: 3, left: 0, blur: 6, opacity: 0.15 } : { enabled: false },
                            },
                            stroke: isAreaOrLine ? { curve: 'smooth', width: 2.5, lineCap: 'round', ...(options.stroke || {}) } : (options.stroke || {}),
                            fill: isAreaOrLine ? {
                                type: 'gradient',
                                gradient: {
                                    shade: isDark ? 'dark' : 'light',
                                    type: 'vertical',
                                    shadeIntensity: 0.3,
                                    opacityFrom: 0.35,
                                    opacityTo: 0.05,
                                    stops: [0, 90, 100],
                                },
                                ...(options.fill || {}),
                            } : (options.fill || {}),
                            grid: {
                                show: true,
                                borderColor: gridColor,
                                strokeDashArray: 4,
                                padding: { left: 10, right: 10 },
                                xaxis: { lines: { show: false } },
                                yaxis: { lines: { show: true } },
                                ...(options.grid || {}),
                            },
                            tooltip: {
                                theme: isDark ? 'dark' : 'light',
                                style: { fontSize: '11px', fontFamily: 'inherit' },
                                x: { show: true },
                                y: { formatter: (val) => val !== undefined && val !== null ? val.toLocaleString() : '' },
                                marker: { show: true },
                                fixed: { enabled: false },
                                ...(options.tooltip || {}),
                            },
                            legend: {
                                show: options.legend?.show !== false && options.legend?.show !== 'false',
                                position: 'bottom',
                                horizontalAlign: 'center',
                                fontSize: '11px',
                                fontWeight: 500,
                                markers: { width: 8, height: 8, radius: 8 },
                                itemMargin: { horizontal: 8, vertical: 2 },
                                labels: { colors: [textMuted] },
                                ...(options.legend || {}),
                            },
                            dataLabels: {
                                enabled: isDonutOrPie,
                                style: { fontSize: '12px', fontWeight: 600 },
                                dropShadow: { enabled: false },
                                ...(options.dataLabels || {}),
                            },
                            xaxis: {
                                ...(options.xaxis || {}),
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: {
                                    style: { colors: textMuted, fontSize: '11px', fontWeight: 500 },
                                    ...(options.xaxis?.labels || {}),
                                },
                            },
                            yaxis: Array.isArray(options.yaxis) ? options.yaxis : {
                                ...(options.yaxis || {}),
                                labels: {
                                    style: { colors: textMuted, fontSize: '11px', fontWeight: 500 },
                                    formatter: (val) => val !== undefined && val !== null ? val.toLocaleString() : '',
                                    ...(options.yaxis?.labels || {}),
                                },
                            },
                            plotOptions: {
                                ...(options.plotOptions || {}),
                                bar: { borderRadius: 6, columnWidth: '60%', ...(options.plotOptions?.bar || {}) },
                                pie: {
                                    donut: {
                                        size: '70%',
                                        labels: {
                                            show: true,
                                            name: { show: true, fontSize: '13px', color: textMuted },
                                            value: { show: true, fontSize: '22px', fontWeight: 700, color: textColor },
                                            total: { show: true, fontSize: '13px', color: textMuted },
                                        },
                                    },
                                    ...(options.plotOptions?.pie || {}),
                                },
                                radialBar: {
                                    hollow: { size: '65%' },
                                    track: { background: gridColor },
                                    dataLabels: {
                                        name: { fontSize: '13px', color: textMuted },
                                        value: { fontSize: '22px', fontWeight: 700, color: textColor },
                                    },
                                    ...(options.plotOptions?.radialBar || {}),
                                },
                            },
                            colors: options.colors || undefined,
                        };

                        const chart = new ApexCharts(el, themedOptions);
                        chart.render().then(() => {
                            el.closest('.chart-wrapper')?.classList.remove('skeleton');
                            this.fluteCharts[chartId] = chart;
                            this.chartRendering[chartId] = false;
                        }).catch(error => {
                            console.error(`Chart render error for #${chartId}:`, error);
                            this.chartRendering[chartId] = false;
                        });
                    } catch (e) {
                        console.error(`ApexCharts instantiation error for #${chartId}:`, e);
                        this.chartRendering[chartId] = false;
                    }
                };

                if (typeof ApexCharts === 'undefined') {
                    this.waitForCDN(start);
                } else {
                    start();
                }
            },

            processCharts(rootNode = document) {
                if (!rootNode || typeof rootNode.querySelectorAll !== 'function') return;
                const chartElements = rootNode.querySelectorAll('[data-chart-options]');
                chartElements.forEach(el => this.processSingleChart(el));
            },

            processSingleChart(el) {
                if (!el.id) {
                    console.warn('Chart element is missing an ID, cannot render.', el);
                    return;
                }
                if (el.querySelector('.apexcharts-canvas')) {
                    return;
                }

                let options;
                try {
                    options = JSON.parse(atob(el.dataset.chartOptions));
                } catch (e) {
                    console.error(`Failed to parse chart options for #${el.id}`, e);
                    return;
                }
                this.renderChart(el, options);
            },

            waitForCDN(callback) {
                const interval = setInterval(() => {
                    if (typeof ApexCharts !== 'undefined') {
                        clearInterval(interval);
                        callback();
                    }
                }, 100);
            },

            init() {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => this.processCharts(document));
                } else {
                    this.processCharts(document);
                }

                const observer = new MutationObserver((mutationsList) => {
                    for (const mutation of mutationsList) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1) { // ELEMENT_NODE
                                    if (node.matches('[data-chart-options]')) {
                                        this.processSingleChart(node);
                                    } else if (node.querySelectorAll) {
                                       node.querySelectorAll('[data-chart-options]').forEach(chartNode => {
                                            this.processSingleChart(chartNode);
                                        });
                                    }
                                }
                            });
                        }
                    }
                });

                observer.observe(document.body, { childList: true, subtree: true });

                window.refreshCharts = (root) => this.processCharts(root);
                window.fluteCharts = this.fluteCharts;
            }
        };

        window.chartManager = chartManager;
        window.chartManager.init();
    } else {
        if (typeof window.refreshCharts === 'function') {
            window.refreshCharts();
        }
    }
</script>
@endonce
