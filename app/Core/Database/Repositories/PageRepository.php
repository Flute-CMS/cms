<?php

namespace Flute\Core\Database\Repositories;

use Cycle\ORM\Select\Repository;

class PageRepository extends Repository
{
    public function findByRoute($route)
    {
        return $this->select()
            ->load(['permissions', 'blocks'])
            ->where('route', $route)
            ->fetchOne();
    }

    public function findByPermission($permission)
    {
        return $this->select()
            ->where('permissions', $permission)
            ->fetchAll();
    }

    public function findWithBlocks()
    {
        // find pages with related blocks
        return $this->select()
            ->load('blocks')
            ->fetchAll();
    }

    public function findByTitle($title)
    {
        // find pages by title
        return $this->select()
            ->where('title', 'LIKE', "%$title%")
            ->fetchAll();
    }
}