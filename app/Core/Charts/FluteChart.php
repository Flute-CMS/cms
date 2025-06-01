<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Traits\HasOptions;
use Nette\Utils\Random;

class FluteChart
{
    use HasOptions;

    /*
    |--------------------------------------------------------------------------
    | Chart
    |--------------------------------------------------------------------------
    |
    | This class build the chart by passing setters to the object, it will 
    | use the method container and scripts to generate a JSON  
    | in blade view
    |
    */

    public string $id;
    protected string $title = '';
    protected string $subtitle = '';
    protected string $subtitlePosition = 'left';
    protected string $type = 'donut';
    protected array $labels = [];
    protected string $fontFamily = 'Manrope';
    protected string $foreColor = '#fff';
    protected string $dataset = '';
    protected int $height = 500;
    protected $width = '100%';
    protected string $colors;
    protected string $horizontal;
    protected string $xAxis;
    protected string $grid;
    protected string $markers;
    protected bool $stacked = false;
    protected bool $showLegend = true;
    protected string $stroke = '';
    protected string $toolbar;
    protected string $zoom;
    protected string $dataLabels;
    protected string $theme = 'dark';
    protected string $background = 'transparent';
    protected string $sparkline;

    protected static $cdnLoaded = false;

    /*
    |--------------------------------------------------------------------------
    | Constructors
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $this->id = Random::generate(10);
        $this->horizontal = json_encode(['horizontal' => false]);
        $this->colors = json_encode([
            '#008FFB',
            '#00E396',
            '#feb019',
            '#ff455f',
            '#775dd0',
            '#80effe',
            '#0077B5',
            '#ff6384',
            '#c9cbcf',
            '#0057ff',
            '#00a9f4',
            '#2ccdc9',
            '#5e72e4'
        ]);
        $this->setXAxis([]);
        $this->fontFamily = 'Manrope';
        $this->grid = json_encode(['show' => false]);
        $this->markers = json_encode(['show' => false]);
        $this->toolbar = json_encode(['show' => false]);
        $this->zoom = json_encode(['enabled' => true]);
        $this->dataLabels = json_encode(['enabled' => false]);
        $this->sparkline = json_encode(['enabled' => false]);
    }

    public function pieChart() : PieChart
    {
        return new PieChart();
    }

    public function donutChart() : DonutChart
    {
        return new DonutChart();
    }

    public function radialChart() : RadialChart
    {
        return new RadialChart();
    }

    public function polarAreaChart() : PolarAreaChart
    {
        return new PolarAreaChart();
    }

    public function lineChart() : LineChart
    {
        return new LineChart();
    }

    public function areaChart() : AreaChart
    {
        return new AreaChart();
    }

    public function barChart() : BarChart
    {
        return new BarChart();
    }

    public function horizontalBarChart() : HorizontalBar
    {
        return new HorizontalBar();
    }

    public function heatMapChart() : HeatMapChart
    {
        return new HeatMapChart();
    }

    public function radarChart() : RadarChart
    {
        return new RadarChart();
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */

    /**
     *
     * @deprecated deprecated since version 2.0
     * @param ?string $type
     * @return $this
     */
    public function setType($type = null) : FluteChart
    {
        $this->type = $type;
        return $this;
    }

    public function setBackground(string $background) : FluteChart
    {
        $this->background = $background;
        return $this;
    }

    public function setFontFamily($fontFamily) : FluteChart
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    public function setFontColor($fontColor) : FluteChart
    {
        $this->foreColor = $fontColor;
        return $this;
    }

    public function setDataset(array $dataset) : FluteChart
    {
        $this->dataset = json_encode($dataset);
        return $this;
    }

    public function setHeight(int $height) : FluteChart
    {
        $this->height = $height;
        return $this;
    }

    public function setWidth($width) : FluteChart
    {
        $this->width = $width;
        return $this;
    }

    public function setColors(array $colors) : FluteChart
    {
        $this->colors = json_encode($colors);
        return $this;
    }

    public function setHorizontal(bool $horizontal) : FluteChart
    {
        $this->horizontal = json_encode(['horizontal' => $horizontal]);
        return $this;
    }

    public function setTitle(string $title) : FluteChart
    {
        $this->title = $title;
        return $this;
    }

    public function setSubtitle(string $subtitle, string $position = 'left') : FluteChart
    {
        $this->subtitle = $subtitle;
        $this->subtitlePosition = $position;
        return $this;
    }

    public function setLabels(array $labels) : FluteChart
    {
        $this->labels = $labels;
        return $this;
    }

    public function setXAxis(array $categories) : FluteChart
    {
        $this->xAxis = json_encode($categories);
        return $this;
    }

    public function setGrid($color = '#e5e5e5', $opacity = 0.1) : FluteChart
    {
        $this->grid = json_encode([
            'show' => true,
            'row' => [
                'colors' => [$color, 'transparent'],
                'opacity' => $opacity,
            ],
        ]);

        return $this;
    }

    public function setMarkers($colors = [], $width = 4, $hoverSize = 7) : FluteChart
    {
        if (empty($colors)) {
            $colors = [
                '#008FFB',
                '#00E396',
                '#feb019',
                '#ff455f',
                '#775dd0',
                '#80effe',
                '#0077B5',
                '#ff6384',
                '#c9cbcf',
                '#0057ff',
                '#00a9f4',
                '#2ccdc9',
                '#5e72e4'
            ];
        }

        $this->markers = json_encode([
            'size' => $width,
            'colors' => $colors,
            'strokeColors' => "#fff",
            'strokeWidth' => $width / 2,
            'hover' => [
                'size' => $hoverSize,
            ]
        ]);

        return $this;
    }

    public function setStroke(int $width, array $colors = [], string $curve = 'straight') : FluteChart
    {
        if (empty($colors)) {
            $colors = [
                '#008FFB',
                '#00E396',
                '#feb019',
                '#ff455f',
                '#775dd0',
                '#80effe',
                '#0077B5',
                '#ff6384',
                '#c9cbcf',
                '#0057ff',
                '#00a9f4',
                '#2ccdc9',
                '#5e72e4'
            ];
        }

        $this->stroke = json_encode([
            'show' => true,
            'width' => $width,
            'colors' => $colors,
            'curve' => $curve,
        ]);
        return $this;
    }

    public function setToolbar(bool $show, bool $zoom = true) : FluteChart
    {
        $this->toolbar = json_encode(['show' => $show]);
        $this->zoom = json_encode(['enabled' => $zoom ? $zoom : false]);
        return $this;
    }

    public function setDataLabels(bool $enabled = true) : FluteChart
    {
        $this->dataLabels = json_encode(['enabled' => $enabled]);
        return $this;
    }

    public function setTheme(string $theme) : FluteChart
    {
        $this->theme = $theme;
        return $this;
    }

    public function setSparkline(bool $enabled = true) : FluteChart
    {
        $this->sparkline = json_encode(['enabled' => $enabled]);
        return $this;
    }

    public function setStacked(bool $stacked = true) : FluteChart
    {
        $this->stacked = $stacked;
        return $this;
    }

    public function setShowLegend(bool $showLegend = true) : self
    {
        $this->showLegend = $showLegend;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    public function transformLabels(array $array)
    {
        $stringArray = array_filter($array, function ($string) {
            return "{$string}";
        });
        return json_encode(['"' . implode('","', $stringArray) . '"']);
    }

    public function container()
    {
        return render('Core/Charts/Views/container', ['id' => $this->id(), 'height' => $this->height, 'width' => $this->width]);
    }

    public function script()
    {
        return render('Core/Charts/Views/script', ['chart' => $this]);
    }

    public function cdn() : string
    {
        if (self::$cdnLoaded)
            return "";

        self::$cdnLoaded = true;

        $cdnLink = asset('assets/js/libs/apex-charts.js');

        return "<script src=$cdnLink></script>";
    }

    // public static function cdn(): string
    // {
    //     return 'https://cdn.jsdelivr.net/npm/apexcharts';
    // }

    /**
     * @return string
     */
    public function background()
    {
        return $this->background;
    }

    public function id() : string
    {
        return $this->id;
    }

    public function title() : string
    {
        return $this->title;
    }

    public function subtitle() : string
    {
        return $this->subtitle;
    }

    public function subtitlePosition() : string
    {
        return $this->subtitlePosition;
    }

    public function type() : string
    {
        return $this->type;
    }

    public function fontFamily() : string
    {
        return $this->fontFamily;
    }

    public function foreColor() : string
    {
        return $this->foreColor;
    }

    public function labels() : array
    {
        return $this->labels;
    }

    public function dataset() : string
    {
        return $this->dataset;
    }

    public function height() : int
    {
        return $this->height;
    }

    public function width() : string
    {
        return $this->width;
    }

    public function colors()
    {
        return $this->colors;
    }

    public function horizontal()
    {
        return $this->horizontal;
    }

    public function xAxis() : string
    {
        return $this->xAxis;
    }

    public function grid()
    {
        return $this->grid;
    }

    public function markers()
    {
        return $this->markers;
    }

    public function stroke() : string
    {
        return $this->stroke;
    }

    public function toolbar()
    {
        return $this->toolbar;
    }

    public function zoom()
    {
        return $this->zoom;
    }

    public function dataLabels()
    {
        return $this->dataLabels;
    }

    public function sparkline()
    {
        return $this->sparkline;
    }

    public function theme()
    {
        return $this->theme;
    }

    public function stacked() : bool
    {
        return $this->stacked;
    }

    public function showLegend() : string
    {
        return $this->showLegend ? 'true' : 'false';
    }

    /*
    |--------------------------------------------------------------------------
    | JSON Options Builder
    |--------------------------------------------------------------------------
    */

    public function toJson()
    {
        $options = [
            'chart' => [
                'type' => $this->type(),
                'height' => $this->height(),
                'width' => $this->width(),
                'toolbar' => json_decode($this->toolbar()),
                'zoom' => json_decode($this->zoom()),
                'fontFamily' => json_decode($this->fontFamily()),
                'foreColor' => $this->foreColor(),
                'sparkline' => $this->sparkline(),
                'stacked' => $this->stacked(),
            ],
            'plotOptions' => [
                'bar' => json_decode($this->horizontal()),
            ],
            'colors' => json_decode($this->colors()),
            'series' => json_decode($this->dataset()),
            'dataLabels' => json_decode($this->dataLabels()),
            'theme' => [
                'mode' => $this->theme
            ],
            'title' => [
                'text' => $this->title()
            ],
            'subtitle' => [
                'text' => $this->subtitle() ? $this->subtitle() : '',
                'align' => $this->subtitlePosition() ? $this->subtitlePosition() : '',
            ],
            'xaxis' => [
                'categories' => json_decode($this->xAxis()),
            ],
            'grid' => json_decode($this->grid()),
            'markers' => json_decode($this->markers()),
            'legend' => [
                'show' => $this->showLegend()
            ],
        ];

        if ($this->labels()) {
            $options['labels'] = $this->labels();
        }

        if ($this->stroke()) {
            $options['stroke'] = json_decode($this->stroke());
        }

        return json([
            'id' => $this->id(),
            'options' => $options,
        ]);
    }
}