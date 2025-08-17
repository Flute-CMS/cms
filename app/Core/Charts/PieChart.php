<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Contracts\MustAddSimpleData;
use Flute\Core\Charts\Traits\SimpleChartDataAggregator;

class PieChart extends FluteChart implements MustAddSimpleData
{
    use SimpleChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'pie';
    }

    public function addPieces(array $data): PieChart
    {
        $this->addData($data);

        return $this;
    }
}
