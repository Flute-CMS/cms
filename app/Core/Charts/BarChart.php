<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Contracts\MustAddComplexData;
use Flute\Core\Charts\Traits\ComplexChartDataAggregator;

class BarChart extends FluteChart implements MustAddComplexData
{
    use ComplexChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'bar';
    }

    public function addBar(string $name, array $data): BarChart
    {
        return $this->addData($name, $data);
    }
}
