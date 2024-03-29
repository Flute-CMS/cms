<?php

namespace Flute\Core\Charts;


use Flute\Core\Charts\Contracts\MustAddSimpleData;
use Flute\Core\Charts\Traits\SimpleChartDataAggregator;

class RadialChart extends FluteChart implements MustAddSimpleData
{
    use SimpleChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'radialBar';
    }

    public function addRings(array $data) :RadialChart
    {
        $this->addData($data);
        return $this;
    }
}