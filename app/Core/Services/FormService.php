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

        $this->setRenderer(new CustomFormRenderer());
    }

    public function csrf()
    {
        return $this['x_csrf_token'] = (new HiddenField)
			->setDefaultValue(template()->getBlade()->getCsrfToken());
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
