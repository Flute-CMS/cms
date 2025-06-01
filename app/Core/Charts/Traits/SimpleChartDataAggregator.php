<?php

namespace Flute\Core\Charts\Traits;

trait SimpleChartDataAggregator
{
    public function addData(array $data) :self
    {
        $this->dataset = json_encode($data);

        return $this;
    }
}