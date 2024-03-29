<?php

namespace Flute\Core\Page\Parsers;

use Flute\Core\Contracts\ParserInterface;
use Nette\Utils\Html;

class WidgetParser implements ParserInterface
{
    public function parse(array $array, string $id)
    {
        try {
            $widget = widgets()->get($array['loader']);

            if( $widget->isLazyLoad() )
            {
                $containerDiv = Html::el('div');
                $containerDiv->id = $id;
                $containerDiv->setHtml(widgets()->loadAndRenderWidget($array['loader'], $this->normalizeSettings($array['settings']), $id));

                return $containerDiv->toHtml();
            }

            return widgets()->loadAndRenderWidget($array['loader'], $this->normalizeSettings($array['settings']), $id);
        } catch (\RuntimeException $e) {
            logs()->error($e);

            flash()->set('warning', __('def.widget_has_errors', ['%name%' => $array['name']]));
        } 
    }

    protected function normalizeSettings(array $settings)
    {
        $result = [];
        foreach ($settings as $key => $value)
            $result[$value['name']] = $value['result'] ?? false;

        return $result;
    }
}