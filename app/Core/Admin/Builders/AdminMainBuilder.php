<?php

namespace Flute\Core\Admin\Builders;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\Charts\FluteChart;


class AdminMainBuilder implements AdminBuilderInterface
{
    protected static array $charts = [];

    public function build(AdminBuilder $adminBuilder): void
    {
    }

    public static function add(FluteChart $chart, int $colMd = 6): void
    {
        self::$charts[$chart->id()] = ['class' => $chart, 'col-md' => $colMd];
    }

    public static function all(): array
    {
        return self::$charts;
    }
}