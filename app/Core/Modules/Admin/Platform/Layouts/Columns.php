<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Support\Arr;

/**
 * Class Columns.
 */
abstract class Columns extends Layout
{
    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.columns';

    /**
     * Layout constructor.
     *
     * @param Layout[] $layouts
     */
    public function __construct(array $layouts = [])
    {
        $this->layouts = $layouts;
    }

    public function skeletonDescriptor(): array
    {
        $columns = [];

        foreach ($this->layouts as $group) {
            $col = [];
            foreach (Arr::wrap($group) as $layout) {
                $layout = is_object($layout) ? $layout : app()->get($layout);
                if ($layout instanceof Layout && $layout->isVisible()) {
                    $col[] = $layout->skeletonDescriptor();
                }
            }
            $columns[] = $col;
        }

        return [
            'type' => 'columns',
            'columns' => $columns,
        ];
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }
}
