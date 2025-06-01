<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Commander;
use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Flute\Core\Charts\FluteChart;

class Chart extends Layout
{
    use Commander;

    /**
     * Путь до blade-шаблона (аналогично другим layout-ам).
     *
     * @var string
     */
    protected $template = 'admin::partials.layouts.chart';

    protected $chart = null;

    /**
     * Заголовок графика.
     *
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * Краткое описание под графиком.
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * Целевое поле (ключ), откуда брать данные в репозитории.
     *
     * @var string
     */
    protected string $target = '';

    /**
     * Тип чарта.
     * donut, bar, line, area, и т.д.
     *
     * @var string
     */
    protected string $type = 'line';

    /**
     * Данные для самого чарта.
     * Вы можете вынести логику получения dataset в build(),
     * либо устанавливать массив напрямую.
     *
     * @var array
     */
    protected array $dataset = [];

    /**
     * Подписи для осей/серий.
     *
     * @var array
     */
    protected array $labels = [];

    /**
     * Высота чарта (px).
     *
     * @var int
     */
    protected int $height = 250;

    /**
     * Ширина чарта (по умолчанию 100%).
     *
     * @var string|int
     */
    protected string|int $width = '100%';

    /**
     * Цвета, используемые в чарте.
     *
     * @var string[]|null
     */
    protected ?array $colors = null;

    /**
     * Фоновый цвет (по умолчанию transparent).
     *
     * @var string|null
     */
    protected ?string $background = null;

    protected ?string $popover = null;

    /**
     * Создает новый Chart layout.
     *
     * @param string $target
     * @param string|null $title
     * @return static
     */
    public static function make(string $target, ?string $title = null) : static
    {
        $instance = new static();
        $instance->target($target);

        if (!is_null($title)) {
            $instance->title($title);
        }

        return $instance;
    }

    /**
     * Установить цель (target), откуда берем данные.
     *
     * @param  string  $target
     * @return $this
     */
    public function target(string $target) : static
    {
        $this->target = $target;

        return $this;
    }

    public function popover(?string $popover = null) : self
    {
        $this->popover = $popover;

        return $this;
    }

    /**
     * Установить заголовок чарта.
     *
     * @param  string|null  $title
     * @return $this
     */
    public function title(?string $title) : static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Установить описание.
     *
     * @param  string|null  $description
     * @return $this
     */
    public function description(?string $description) : static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Установить тип чарта (line, bar, donut и т.д.).
     *
     * @param  string  $type
     * @return $this
     */
    public function type(string $type) : static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Установить высоту чарта (px).
     *
     * @param  int  $height
     * @return $this
     */
    public function height(int $height) : static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Установить ширину (по умолчанию строка "100%", можно задать и в px).
     *
     * @param  string|int  $width
     * @return $this
     */
    public function width(string|int $width) : static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Установить цвета графиков.
     *
     * @param  array  $colors
     * @return $this
     */
    public function colors(array $colors) : static
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Установить фон.
     *
     * @param  string  $background
     * @return $this
     */
    public function background(string $background) : static
    {
        $this->background = $background;

        return $this;
    }

    /**
     * Установить вручную данные для чарта (dataset).
     *
     * @param  array  $dataset
     * @return $this
     */
    public function dataset(array $dataset) : static
    {
        $this->dataset = $dataset;

        return $this;
    }

    /**
     * Установить подписи (labels).
     *
     * @param  array  $labels
     * @return $this
     */
    public function labels(array $labels) : static
    {
        $this->labels = $labels;

        return $this;
    }

    public function from(FluteChart $chart) : self
    {
        $this->chart = $chart;

        return $this;
    }

    /**
     * Основной метод рендера.  
     * Здесь формируем FluteChart и передаем его в шаблон.
     *
     * @param  Repository  $repository
     * @return \Illuminate\Contracts\View\View|void
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible()) {
            return;
        }

        if ($this->chart) {
            $chart = $this->chart;
        } else {
            $chart = new FluteChart();
            $chart->setType($this->type);
        }

        if ($this->background) {
            $chart->setBackground($this->background);
        }

        if($this->height) {
            $chart->setHeight($this->height);
        }

        if ($this->width) {
            $chart->setWidth($this->width);
        }

        if (!empty($this->colors)) {
            $chart->setColors($this->colors);
        }

        $content = $repository->getContent($this->target);

        $finalDataset = !empty($this->dataset) ? $this->dataset : (array) $content;

        $chart->setDataset($finalDataset);

        if (!empty($this->labels)) {
            $chart->setLabels($this->labels);
        }

        $commandBar = $this->buildCommandBar($repository);

        return view($this->template, [
            'repository' => $repository,
            'title' => $this->title,
            'description' => $this->description,
            'popover' => $this->popover,
            'commandBar' => $commandBar,
            'chart' => $chart,
        ]);
    }
}
