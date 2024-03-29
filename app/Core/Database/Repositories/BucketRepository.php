<?php

namespace Flute\Core\Database\Repositories;

use Flute\Core\Database\Entities\Bucket;
use Cycle\ORM\Select\Repository;

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
