<?php


namespace Flute\Core\Charts;


use Flute\Core\Charts\Contracts\MustAddComplexData;
use Flute\Core\Charts\Traits\ComplexChartDataAggregator;

class HeatMapChart extends FluteChart implements MustAddComplexData
{
    use ComplexChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'heatmap';
    }

    public function addHeat(string $name, array $data) :HeatMapChart
    {
        return $this->addData($name, $data);
    }
}