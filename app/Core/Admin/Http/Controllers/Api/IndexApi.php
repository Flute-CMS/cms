<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TablePreparation;

class IndexApi extends AbstractController
{
    public function index(FluteRequest $request)
    {
        $page = ($request->get("start", 1) + $request->get('length')) / $request->get('length');
        $draw = (int) $request->get("draw", 1);

        $select = rep(User::class)->select()->orderBy('id', 'DESC');

        $paginator = new \Spiral\Pagination\Paginator($request->get('length'));
        $paginate = $paginator->withPage($page)->paginate($select);

        return $this->json([
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(['avatar', 'name',  'login', 'uri'], $select->fetchAll())
        ]);
    }
}