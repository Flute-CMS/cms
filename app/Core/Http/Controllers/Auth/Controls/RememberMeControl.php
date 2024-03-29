<?php

namespace Flute\Core\Http\Controllers\Auth\Controls;

use Nette\Forms\Controls\Checkbox;
use Nette\Utils\Html;

class RememberMeControl extends Checkbox
{
    public function getControl() : Html
    {
        $link = null;
        $control = null;

        if( app('auth.remember_me') )
            $control = parent::getControl();

        if( app('auth.reset_password') )
            $link = Html::el('a')
                ->href("/reset")
                ->setText(__('auth.lost_password'))
                ->addAttributes(['class' => 'remember_me_link']);

        return Html::el('div')
            ->addHtml($control)
            ->addHtml($link);
    }
}
