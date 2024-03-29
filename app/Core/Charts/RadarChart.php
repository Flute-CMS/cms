<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Contracts\MustAddComplexData;
use Flute\Core\Charts\Traits\ComplexChartDataAggregator;

class RadarChart extends FluteChart implements MustAddComplexData
{
    use ComplexChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'radar';
    }

    public function addSerie(string $name, array $data) :RadarChart
    {
        return $this->addData($name, $data);
    }
}