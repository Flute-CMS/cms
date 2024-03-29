<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Charts\FluteChart;

if (!function_exists("chart")) {
    /**
     * Get the FluteCharts instance
     * 
     * @return FluteChart
     * 
     * @throws DependencyException
     * @throws NotFoundException
     */
    function chart()
    {
        /** @var FluteChart $cache */
        return app(FluteChart::class);
    }
}
