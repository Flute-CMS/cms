<?php

namespace Flute\Core\Services;

use Flute\Core\Support\CustomFormRenderer;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Form;

class FormService extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->csrf();

        $this->setRenderer(new CustomFormRenderer());
    }

    public function csrf()
    {
        if (isset($this['x_csrf_token']))
            return $this['x_csrf_token'];

        return $this['x_csrf_token'] = (new HiddenField)
            ->setDefaultValue(csrf_token());
    }

    public function gy(int $val): self
    {
        $this->getRenderer()->gy($val);

        return $this;
    }

    public function gx(int $val): self
    {
        $this->getRenderer()->gx($val);

        return $this;
    }
}
