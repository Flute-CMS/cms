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
                        const themedOptions = {
                            ...options,
                            chart: {
                                ...options.chart,
                                foreColor: getComputedStyle(document.documentElement).getPropertyValue('--text')?.trim() || options.chart?.foreColor,
                                fontFamily: 'SF Pro Display, Manrope, Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif',
                                background: 'transparent',
                            },
                            grid: {
                                ...options.grid,
                                borderColor: getComputedStyle(document.documentElement).getPropertyValue('--transp-1')?.trim() || '#e5e5e5',
                            },
                            tooltip: {
                                theme: document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark',
                                style: { fontSize: '12px' },
                            },
                            legend: {
                                ...options.legend,
                                fontSize: '12px',
                                labels: { colors: getComputedStyle(document.documentElement).getPropertyValue('--text-400')?.trim() || undefined },
                            },
                            colors: options.colors?.map?.(c => c) || options.colors,
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
