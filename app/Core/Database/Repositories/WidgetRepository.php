<?php

namespace Flute\Core\Database\Repositories;

use Cycle\ORM\Select\Repository;
use Flute\Core\Database\Entities\Widget;

class WidgetRepository extends Repository
{
    public function findByName(string $name): ?Widget
    {
        return $this->select()
                    ->where('name', $name)
                    ->fetchOne();
    }

    public function findByLoader(string $loader): ?Widget
    {
        return $this->select()
                    ->where('loader', $loader)
                    ->fetchOne();
    }

    public function findById(int $id): ?Widget
    {
        return $this->findByPK($id);
    }
}
