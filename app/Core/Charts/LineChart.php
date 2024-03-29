<?php

namespace Flute\Core\Charts;


use Flute\Core\Charts\Contracts\MustAddComplexData;
use Flute\Core\Charts\Traits\ComplexChartDataAggregator;

class LineChart extends FluteChart implements MustAddComplexData
{
    use ComplexChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'line';
    }

    public function addLine(string $name, array $data) :LineChart
    {
        return $this->addData($name, $data);
    }
}