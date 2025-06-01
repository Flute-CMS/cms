<?php

namespace Flute\Admin\Platform\Layouts;

use Flute\Admin\Platform\Action;
use Flute\Admin\Platform\Content;
use Flute\Admin\Platform\Contracts\Actionable;
use Flute\Admin\Platform\Contracts\Cardable;
use Illuminate\View\View;

class Card extends Content
{
    /**
     * @var string
     */
    protected $template = 'admin::partials.layouts.card';

    /**
     * @var array|Action[]
     */
    protected $commandBar;

    /**
     * Card constructor.
     *
     * @param string|Cardable $target
     * @param Action[]        $commandBar
     */
    public function __construct($target, array $commandBar = [])
    {
        parent::__construct($target);

        $this->commandBar = $commandBar;
    }

    public function render(Cardable $card): View
    {
        return view($this->template, [
            'title'       => $card->title(),
            'description' => $card->description(),
            'image'       => $card->image(),
            'commandBar'  => $this->buildCommandBar(),
            'color'       => $card->color()?->name(),
        ]);
    }

    private function buildCommandBar(): array
    {
        return collect($this->commandBar)
            ->map(fn (Actionable $command) => $command->build($this->query))->all();
    }
}
