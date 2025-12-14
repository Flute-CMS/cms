<?php

namespace Flute\Core\Charts;

use Flute\Core\Charts\Contracts\MustAddSimpleData;
use Flute\Core\Charts\Traits\SimpleChartDataAggregator;

class PolarAreaChart extends FluteChart implements MustAddSimpleData
{
    use SimpleChartDataAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->type = 'polarArea';
    }

    public function addPolarAreas(array $data): PolarAreaChart
    {
        $this->addData($data);

        return $this;
    }
}
