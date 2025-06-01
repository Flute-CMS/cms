<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Layout;
use Flute\Admin\Platform\Repository;
use Illuminate\Contracts\View\Factory;

/**
 * Class Metric.
 */
class Metric extends Layout
{
    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.metric';

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var array
     */
    protected $icons = [];

    /**
     * Create a new Metric instance
     * 
     * @param array $labels
     */
    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * Set icon for specific metric
     * 
     * @param string $label
     * @param string $icon
     * @return $this
     */
    public function setIcon(string $label, string $icon): self
    {
        $this->icons[$label] = $icon;
        return $this;
    }

    /**
     * Set multiple icons at once
     * 
     * @param array $icons
     * @return $this
     */
    public function setIcons(array $icons): self
    {
        $this->icons = array_merge($this->icons, $icons);
        return $this;
    }

    /**
     * Get icon for metric
     * 
     * @param string $label
     * @return string|null
     */
    protected function getIcon(string $label): ?string
    {
        return $this->icons[$label] ?? null;
    }

    /**
     * Build the layout
     * 
     * @param Repository $repository
     * @return Factory|\Illuminate\View\View|void
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (!$this->isVisible() || empty($this->labels)) {
            return;
        }
        
        $metrics = collect($this->labels)->mapWithKeys(function(string $value, string $key) use ($repository) {
            return [
                $key => [
                    'value' => $repository->getContent($value, ''),
                    'icon' => $this->getIcon($key)
                ]
            ];
        });

        return view($this->template, [
            'title' => $this->title,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Set title for metrics group
     * 
     * @param string $title
     * @return $this
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }
}
