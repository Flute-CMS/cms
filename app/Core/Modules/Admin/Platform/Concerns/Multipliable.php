<?php

namespace Flute\Admin\Platform\Concerns;

use Illuminate\Support\Str;

trait Multipliable
{
    /**
     * @return $this
     */
    public function multiple(): self
    {
        $this->set('multiple', 'multiple');

        $this->inlineAttributes[] = 'multiple';

        return $this->addBeforeRender(function () {
            $name = $this->get('name');

            $this->set('name', Str::finish($name, '[]'));
        });
    }
}
