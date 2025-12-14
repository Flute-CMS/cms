<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: "Flute\Core\Database\Repositories\BucketRepository")]
class Bucket extends ActiveRecord
{
    #[Column(type: "string", primary: true)]
    private string $id;

    #[Column(type: "integer")]
    private int $tokens;

    #[Column(type: "integer")]
    private int $replenishedAt;

    #[Column(type: "integer")]
    private int $expiresAt = 0;

    public function getId() : string
    {
        return $this->id;
    }

    public function setId(string $id) : self
    {
        $this->id = $id;
        return $this;
    }

    public function getTokens() : int
    {
        return $this->tokens;
    }

    public function setTokens(int $tokens) : self
    {
        $this->tokens = $tokens;
        return $this;
    }

    public function getReplenishedAt() : int
    {
        return $this->replenishedAt;
    }

    public function setReplenishedAt(int $replenishedAt) : self
    {
        $this->replenishedAt = $replenishedAt;
        return $this;
    }

    public function getExpiresAt() : int
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(int $expiresAt) : self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }
}
