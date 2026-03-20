<?php

namespace Flute\Admin\Platform;

use Flute\Admin\Platform\Contracts\Actionable;

trait Commander
{
    /**
     * @return []
     */
    protected function commandBar(): array
    {
        return [];
    }

    protected function buildCommandBar(Repository $repository): array
    {
        return collect($this->commandBar())
            ->map(static fn(Actionable $command) => $command->build($repository))
            ->filter()
            ->all();
    }
}
