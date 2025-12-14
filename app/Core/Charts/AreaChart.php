<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Contracts\MustAddComplexData;
use Flute\Core\Charts\Traits\ComplexChartDataAggregator;

class AreaChart extends FluteChart implements MustAddComplexData
{
    use ComplexChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'area';
    }

    public function addArea(string $name, array $data): AreaChart
    {
        return $this->addData($name, $data);
    }
}
