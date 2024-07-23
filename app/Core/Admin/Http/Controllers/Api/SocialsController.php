<?php

namespace Flute\Core\Admin\Http\Controllers\Api;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class SocialsController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.socials');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        if (!class_exists(sprintf('Hybridauth\\Provider\\%s', $request->key)))
            return $this->error(__('admin.socials.social_not_exists', ['name' => $request->key]), 404);

        if (!$request->settings)
            return $this->error(__('admin.socials.min_one_value'));

        $social = rep(SocialNetwork::class)->findOne([
            'key' => $request->key,
        ]);

        if ($social)
            return $this->error(__('admin.socials.exists', ['name' => $request->key]), 403);

        $social = new SocialNetwork;
        $social->icon = $request->icon;
        $social->key = $request->key;
        $social->cooldownTime = (int) $request->cooldownTime;
        $social->allowToRegister = filter_var($request->allowToRegister, FILTER_VALIDATE_BOOL) ?? true;
        $social->enabled = filter_var($request->enabled, FILTER_VALIDATE_BOOL) ?? false;
        $social->settings = $request->settings;

        user()->log('events.social_added', $request->key);

        transaction($social)->run();

        return $this->success();
    }

    public function enable(FluteRequest $request, string $id)
    {
        $social = $this->getSocial((int) $id);

        if (!$social)
            return $this->error(__('admin.socials.not_found'), 404);

        $social->enabled = true;

        user()->log('events.social_enabled', $id);

        transaction($social)->run();

        return $this->success();
    }

    public function disable(FluteRequest $request, string $id)
    {
        $social = $this->getSocial((int) $id);

        if (!$social)
            return $this->error(__('admin.socials.not_found'), 404);

        $social->enabled = false;

        user()->log('events.social_disabled', $id);

        transaction($social)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $social = $this->getSocial((int) $id);

        if (!$social)
            return $this->error(__('admin.socials.not_found'), 404);

        user()->log('events.social_deleted', $id);

        transaction($social, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        if (!class_exists(sprintf('Hybridauth\\Provider\\%s', $request->key)))
            return $this->error(__('admin.socials.social_not_exists', ['name' => $request->key]), 404);

        if (!$request->settings)
            return $this->error(__('admin.socials.min_one_value'));

        $social = rep(SocialNetwork::class)->findOne([
            'id' => $id,
        ]);

        if (!$social)
            return $this->error(__('admin.socials.exists', ['name' => $request->key]), 403);

        $social->icon = $request->icon;
        $social->key = $request->key;
        $social->allowToRegister = filter_var($request->allowToRegister, FILTER_VALIDATE_BOOL) ?? true;
        $social->enabled = filter_var($request->enabled, FILTER_VALIDATE_BOOL) ?? false;
        $social->settings = $request->settings;
        $social->cooldownTime = (int) $request->cooldownTime;

        user()->log('events.social_changed', $id);

        transaction($social)->run();

        return $this->success();
    }

    protected function getSocial(int $id)
    {
        return rep(SocialNetwork::class)->findByPK($id);
    }
}