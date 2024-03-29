<?php

namespace Flute\Core\Support;

use Nette\Forms\Rendering\DefaultFormRenderer;

class CustomFormRenderer extends DefaultFormRenderer
{
    private int $gxClass = 3;
    private int $gyClass = 3;

    public function __construct()
    {
        // Настройки рендерера
        $this->wrappers['controls']['container'] = 'div class="row"';
        $this->wrappers['pair']['container'] = 'div class="input-form"';
        $this->wrappers['pair']['.error'] = 'has-error';
        $this->wrappers['control']['container'] = null;
        $this->wrappers['label']['container'] = null;

        $this->setGXY();
    }

    public function renderPair(\Nette\Forms\Control $control): string
    {
        $pair = $this->getWrapper('pair container');

        $pair->addHtml($this->renderControl($control))
            ->addHtml($this->renderLabel($control))
            ->class($this->getValue($control->isRequired() ? 'pair .required' : 'pair .optional'), true)
            ->class($control->hasErrors() ? $this->getValue('pair .error') : null, true)
            ->class($control->getOption('class'), true);

        if (++$this->counter % 2) {
            $pair->class($this->getValue('pair .odd'), true);
        }

        $pair->id = $control->getOption('id');

        // Создаем новый div с классом col-md и оборачиваем в него pair
        $col_md = $control->getOption('col-md') ?? 12;
        $colMdWrapper = \Nette\Utils\Html::el('div')->class("col-md-{$col_md}");
        $colMdWrapper->addHtml($pair);

        return $colMdWrapper->render(0);
    }

    public function gy(int $val): self
    {
        $this->gyClass = $val;

        $this->setGXY();

        return $this;
    }

    public function gx(int $val): self
    {
        $this->gxClass = $val;

        $this->setGXY();

        return $this;
    }

    protected function setGXY()
    {
        $gx = $this->gxClass;
        $gy = $this->gyClass;

        if ($gx > 5 || $gx < 0 || $gy > 5 || $gy < 0)
            throw new \InvalidArgumentException("Gap value must be more than 0 and less 5");

        $this->wrappers['controls']['container'] = "div class='row gy-{$gy} gx-{$gx}'";
    }
}