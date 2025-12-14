<?php

namespace Flute\Admin\Platform\Contracts;

interface Groupable extends Fieldable
{
    /**
     * @return \Flute\Admin\Platform\Field[]
     */
    public function getGroup(): array;

    public function setGroup(array $group = []): self;
}
