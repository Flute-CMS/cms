<?php


namespace Flute\Core\Charts\Contracts;


interface MustAddComplexData
{
    public function addData(string $name, array $data);
}