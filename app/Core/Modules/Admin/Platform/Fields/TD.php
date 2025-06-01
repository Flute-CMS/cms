<?php

namespace Flute\Admin\Platform\Fields;

use Flute\Admin\Platform\Fields\Cell;
use Flute\Admin\Platform\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TD extends Cell
{
    /**
     * Align the cell to the left.
     */
    public const ALIGN_LEFT = 'start';
    /**
     * Align the cell to the center.
     */
    public const ALIGN_CENTER = 'center';
    /**
     * Align the cell to the right.
     */
    public const ALIGN_RIGHT = 'end';

    /**
     * @var string|null|int
     */
    protected $width;

    /**
     * @var string|null
     */
    protected $style;

    /**
     * @var string|null
     */
    protected $class;

    /**
     * @var bool
     */
    protected $sort = false;

    /**
     * @var string
     */
    protected $align = self::ALIGN_LEFT;

    /**
     * @var int
     */
    protected $colspan = 1;

    /**
     * @var bool
     */
    protected $allowUserHidden = true;

    /**
     * @var bool
     */
    protected $defaultHidden = false;

    protected $searchable = true;

    /**
     * @var string|null
     */
    protected $minWidth;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param string|int $width
     */
    public function width($width) : self
    {
        $this->width = $width;

        return $this;
    }

    public function minWidth($width) : self
    {
        $this->minWidth = $width;

        return $this;
    }

    /**
     * @param string $style
     */
    public function style(string $style) : self
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @param string $class
     */
    public function class(string $class) : self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Enable sorting for the column
     *
     * @param bool $sort
     */
    public function sort(bool $sort = true) : self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Set alignment for the column
     *
     * @param string $align
     */
    public function align(string $align) : self
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Align the column to the left
     */
    public function alignLeft() : self
    {
        $this->align = self::ALIGN_LEFT;

        return $this;
    }

    /**
     * Align the column to the right
     */
    public function alignRight() : self
    {
        $this->align = self::ALIGN_RIGHT;

        return $this;
    }

    /**
     * Align the column to the center
     */
    public function alignCenter() : self
    {
        $this->align = self::ALIGN_CENTER;

        return $this;
    }

    /**
     * Set colspan for the column
     *
     * @param int $colspan
     */
    public function colspan(int $colspan) : self
    {
        $this->colspan = $colspan;

        return $this;
    }

    /**
     * Builds a column header.
     *
     * @return Factory|View
     */
    public function buildTh() : Factory|View
    {
        $style = $this->style;
        $width = is_numeric($this->width) ? $this->width.'px' : $this->width;
        $minWidth = is_numeric($this->minWidth) ? $this->minWidth.'px' : $this->minWidth;

        // Add hidden style if needed
        if ($this->hasAttribute('hidden')) {
            $style = ($style ? $style.'; ' : '').'display: none;';
        }

        $tableId = $this->getAttribute('tableId', 'default');

        return view('admin::partials.layouts.th', [
            'width' => $width,
            'minWidth' => $minWidth,
            'align' => $this->align,
            'sort' => $this->sort,
            'sortUrl' => $this->buildSortUrl(),
            'column' => $this->column,
            'title' => $this->title,
            'slug' => $this->sluggable(),
            'style' => $style,
            'data_column' => $this->sluggable(),
            'aria_hidden' => $this->hasAttribute('hidden') ? 'true' : null,
            'tableId' => $tableId,
        ]);
    }

    /**
     * Builds content for the column.
     *
     * @param object $source
     * @param object|null $loop
     * @return Factory|View
     */
    public function buildTd($source, ?object $loop = null) : Factory|View
    {
        $value = null;
        $style = $this->style;

        // Add hidden style if needed
        if ($this->hasAttribute('hidden')) {
            $style = ($style ? $style.'; ' : '').'display: none;';
        }

        if ($source instanceof Repository) {
            $value = $this->render
                ? $this->handler($source, $loop)
                : $source->getContent($this->name);
        } else {
            // There was a bug with EntityProxyInterface. #TODO: Implement new ways to display values.
            $value = $this->render
                ? $this->handler($source, $loop)
                : (is_array($source) ? $source[$this->name] : $source->{$this->name});
        }

        return view('admin::partials.layouts.td', [
            'align' => $this->align,
            'value' => $value,
            'render' => $this->render,
            'slug' => $this->sluggable(),
            'width' => is_numeric($this->width) ? $this->width.'px' : $this->width,
            'style' => $style,
            'class' => $this->class,
            'colspan' => $this->colspan,
            'data_column' => $this->sluggable(),
            'aria_hidden' => $this->hasAttribute('hidden') ? 'true' : null,
        ]);
    }

    /**
     * Slugify the column name for HTML attributes.
     *
     * @return string
     */
    protected function sluggable() : string
    {
        return Str::slug($this->name);
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Determine if the column can be hidden by the user.
     *
     * @return bool
     */
    public function isAllowUserHidden() : bool
    {
        return $this->allowUserHidden;
    }

    /**
     * Builds an item menu for showing/hiding the column.
     *
     * @return Factory|View|null
     */
    public function buildItemMenu() : Factory|View|null
    {
        if (! $this->isAllowUserHidden()) {
            return null;
        }

        return view('admin::partials.layouts.selectedTd', [
            'title' => $this->title,
            'slug' => $this->sluggable(),
            'defaultHidden' => var_export($this->defaultHidden, true),
        ]);
    }

    /**
     * Prevent the user from hiding a column in the interface.
     *
     * @param bool $hidden
     */
    public function cantHide(bool $hidden = false) : self
    {
        $this->allowUserHidden = $hidden;

        return $this;
    }

    /**
     * Set the column to be hidden by default.
     *
     * @param bool $hidden
     */
    public function defaultHidden(bool $hidden = true) : self
    {
        $this->defaultHidden = $hidden;

        return $this;
    }

    /**
     * Build the sort URL for the column.
     *
     * @return string
     */
    public function buildSortUrl() : string
    {
        $currentSort = request()->input('sort', '');
        $newSort = ($currentSort === $this->column) ? '-'.$this->column : $this->column;

        $query = array_merge(request()->input(), ['sort' => $newSort]);

        return url()->withGet()->addParams($query)->get();
    }

    /**
     * Disable search for this column.
     */
    public function disableSearch(bool $disable = true) : self
    {
        $this->searchable = ! $disable;

        return $this;
    }

    /**
     * Returns if search is allowed for this column.
     *
     * @return bool
     */
    public function isSearchable() : bool
    {
        return $this->searchable;
    }

    /**
     * Set the search for this column.
     *
     * @param bool $searchable
     */
    public function searchable(bool $searchable = true) : self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Check if any columns are visible.
     *
     * @param TD[] $columns
     * @return bool
     */
    public static function isShowVisibleColumns(array $columns) : bool
    {
        return collect($columns)->filter(fn ($column) => $column->isAllowUserHidden())->isNotEmpty();
    }

    /**
     * Check if the column should be hidden based on user preferences in cookies.
     *
     * @param string $tableId The table identifier
     * @return bool
     */
    public function isHiddenByUserPreference(string $tableId) : bool
    {
        if (! $this->isAllowUserHidden()) {
            return false;
        }

        $cookieKey = "columns_{$tableId}";
        $cookieValue = cookie()->get($cookieKey) ?? null;

        if (! $cookieValue) {
            return $this->defaultHidden;
        }

        try {
            $preferences = json_decode($cookieValue, true);
            $columnSlug = $this->sluggable();

            if (isset($preferences[$columnSlug])) {
                return ! $preferences[$columnSlug];
            }
        } catch (\Exception $e) {
        }

        return $this->defaultHidden;
    }

    /**
     * Set a custom attribute on the column.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $name, $value) : self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Get a custom attribute from the column.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Check if the column has a specific attribute.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name) : bool
    {
        return isset($this->attributes[$name]);
    }
}
