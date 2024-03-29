<?php

namespace Flute\Core\Contracts;
use Flute\Core\Database\Entities\User;

interface ProfileTabInterface {
    public function render( User $user );
    public function getSidebarInfo();
    public function getKey();
}