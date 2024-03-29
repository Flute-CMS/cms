<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Exceptions\DecryptException;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class WidgetController extends AbstractController
{
    /**
     * The action to show widget output via ajax.
     *
     * @param FluteRequest $request
     *
     * @return mixed
     */
    public function showWidget(FluteRequest $request)
    {
        try {
            $params = encrypt()->decrypt($request->input('params'));

            $widget = widgets()->get($params['loader']);

            return response()->json([
                "assets" => $params['assets'],
                "html" => $widget->render($params['settings'])
            ]);
        } catch (\RuntimeException $e) {
            logs()->error($e->getMessage());
            return $this->error(app()->debug() ? $e->getMessage() : 'Widget loader error.');
        } catch (DecryptException $e) {
            return $this->error('Invalid params for widget');
        }
    }

    public function getAllWidgets(FluteRequest $request)
    {
        return response()->json(widgets()->getWidgets());
    }
}