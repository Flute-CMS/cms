<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Contracts\MustAddSimpleData;
use Flute\Core\Charts\Traits\SimpleChartDataAggregator;

class DonutChart extends FluteChart implements MustAddSimpleData
{
    use SimpleChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'donut';
    }

    public function addPieces(array $data): DonutChart
    {
        $this->addData($data);

        return $this;
    }
}
