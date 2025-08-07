<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Field;
use Flute\Admin\Platform\Repository;
use Flute\Core\Traits\MacroableTrait;
use Illuminate\Support\Arr;

class LayoutFactory
{
    use MacroableTrait;

    /**
     * @param \Illuminate\Contracts\Support\Arrayable|array $data
     */
    public static function view(string $view, $data = []): View
    {
        return new class ($view, $data) extends View {};
    }

    public static function rows(array $fields): Rows
    {
        return new class ($fields) extends Rows {
            /**
             * @var \Flute\Admin\Platform\Field[]
             */
            protected $fields;

            /**
             *  constructor.
             */
            public function __construct(array $fields = [])
            {
                $this->fields = $fields;
            }

            public function fields(): array
            {
                return $this->fields;
            }
        };
    }

    public static function block($layouts): Block
    {
        return new class (Arr::wrap($layouts)) extends Block {};
    }

    public static function table(string $target, array $columns): Table
    {
        return new class ($target, $columns) extends Table {
            /**
             * @var array
             */
            protected $columns;

            public function __construct(string $target, array $columns)
            {
                $this->target = $target;
                $this->columns = $columns;
            }

            public function columns(): array
            {
                return $this->columns;
            }
        };
    }

    public static function columns(array $layouts): Columns
    {
        return new class ($layouts) extends Columns {};
    }

    public static function split(array $layouts): Split
    {
        return new class ($layouts) extends Split {};
    }

    public static function tabs(array $tabs): Tabs
    {
        return new class ($tabs) extends Tabs {};
    }

    public static function field(Field $field): \Flute\Admin\Platform\Layouts\Field
    {
        return new class ($field) extends \Flute\Admin\Platform\Layouts\Field {};
    }

    /**
     * @param string|string[] $layouts
     */
    public static function modal(Repository $repository, $layouts): Modal
    {
        $layouts = Arr::wrap($layouts);

        return new class ($repository, $layouts) extends Modal {};
    }

    public static function blank(array $layouts): Blank
    {
        return new class ($layouts) extends Blank {};
    }

    public static function wrapper(string $template, array $layouts): Wrapper
    {
        return new class ($template, $layouts) extends Wrapper {};
    }

    // public static function accordion(array $layouts) : Accordion
    // {
    //     return new class ($layouts) extends Accordion {};
    // }

    // /**
    //  * @param string[] $filters
    //  */
    // public static function selection(array $filters) : Selection
    // {
    //     return new class ($filters) extends Selection {
    //         /**
    //          * @var string[]
    //          */
    //         protected $filters;

    //         /**
    //          * Constructor.
    //          *
    //          * @param string[] $filters
    //          */
    //         public function __construct(array $filters = [])
    //         {
    //             $this->filters = $filters;
    //         }

    //         /**
    //          * @return string[]
    //          */
    //         public function filters() : array
    //         {
    //             return $this->filters;
    //         }
    //     };
    // }

    // public static function legend(string $target, array $columns) : Legend
    // {
    //     return new class ($target, $columns) extends Legend {
    //         /**
    //          * @var array
    //          */
    //         protected $columns;

    //         public function __construct(string $target, array $columns)
    //         {
    //             $this->target = $target;
    //             $this->columns = $columns;
    //         }

    //         public function columns() : array
    //         {
    //             return $this->columns;
    //         }
    //     };
    // }

    // public static function browsing(string $src) : Browsing
    // {
    //     return new Browsing($src);
    // }

    public static function metrics(array $labels): Metric
    {
        return new Metric($labels);
    }

    public static function chart(string $target, ?string $title = null): Chart
    {
        $chart = new class ($target, $title) extends Chart {
            public function __construct(string $target, ?string $title)
            {
                $this->target($target);
                if ($title) {
                    $this->title($title);
                }
            }
        };

        return $chart;
    }

    /**
     * @param string $target
     * @param array  $columns
     *
     * @return \Flute\Admin\Platform\Layouts\Sortable
     */
    public static function sortable(string $target, array $columns): Sortable
    {
        return new class ($target, $columns) extends Sortable {
            /**
             * @var array
             */
            protected $columns;

            public function __construct(string $target, array $columns)
            {
                $this->target = $target;
                $this->columns = $columns;
            }

            public function columns(): iterable
            {
                return $this->columns;
            }
        };
    }
}
