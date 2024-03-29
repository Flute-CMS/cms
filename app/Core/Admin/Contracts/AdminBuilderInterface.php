<?php

namespace Flute\Core\Admin\Contracts;
use Flute\Core\Admin\AdminBuilder;

interface AdminBuilderInterface
{
    /**
     * Run the current builder instance
     * 
     * @return void
     */
    public function build(AdminBuilder $adminBuilder) : void;
}