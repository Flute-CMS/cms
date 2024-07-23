<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\ConditionGroup;
use Flute\Core\Database\Entities\Redirect;
use Flute\Core\Database\Entities\RedirectCondition;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class RedirectsController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.redirects');
    }

    public function add(FluteRequest $request)
    {
        $data = $request->input();

        $redirect = new Redirect($data['from'], $data['to']);

        foreach ($data['conditions'] as $conditionArray) {
            $conditionGroup = new ConditionGroup;
            foreach ($conditionArray as $condition) {
                $redirectCondition = new RedirectCondition(
                    $condition['field'],
                    $condition['value'],
                    $condition['operator']
                );
                $conditionGroup->addCondition($redirectCondition);
            }
            $redirect->addConditionGroup($conditionGroup);
        }

        user()->log('events.redirect_added', $data['from'] . ' ' . $data['to']);

        transaction($redirect)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $redirect = $this->getRedirect((int) $id);

        if (!$redirect)
            return $this->error(__('def.not_found'), 404);

        user()->log('events.redirect_deleted', $id);

        transaction($redirect, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $redirect = $this->getRedirect((int) $id);
        $data = $request->input();

        if (!$redirect)
            return $this->error(__('def.not_found'), 404);

        $redirect->removeConditions();

        foreach ($data['conditions'] as $conditionArray) {
            $conditionGroup = new ConditionGroup;
            foreach ($conditionArray as $condition) {
                $redirectCondition = new RedirectCondition(
                    $condition['field'],
                    $condition['value'],
                    $condition['operator']
                );
                $conditionGroup->addCondition($redirectCondition);
            }
            $redirect->addConditionGroup($conditionGroup);
        }

        $redirect->fromUrl = $data['from'];
        $redirect->toUrl = $data['to'];

        user()->log('events.redirect_changed', $id);

        transaction($redirect)->run();

        return $this->success();
    }

    protected function getRedirect(int $id) : Redirect
    {
        return rep(Redirect::class)->select()->where('id', $id)->load('conditionGroups')->load('conditionGroups.conditions')->fetchOne();
    }
}