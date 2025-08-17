<?php

namespace Flute\Core\Database\Repositories;

use Cycle\ORM\Select\Repository;
use Flute\Core\Database\Entities\Bucket;

class BucketRepository extends Repository
{
    public function findById(string $id)
    {
        return $this->select()
            ->where(['id' => $id])
            ->fetchOne();
    }

    public function save(Bucket $bucket): void
    {
        transaction($bucket)->run();
    }
}
