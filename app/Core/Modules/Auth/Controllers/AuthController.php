<?php

namespace Flute\Core\Modules\Auth\Controllers;

use Exception;
use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class AuthController extends BaseController
{
    public function getLogin(FluteRequest $request)
    {
        breadcrumb()->add(config('app.name'), url('/'))
            ->add(__('auth.header.login'));

        if (config('auth.only_social', false)) {
            $providers = social()->getAll();

            if (sizeof($providers) === 1) {
                $key = array_key_first($providers);

                return redirect(url("social/{$key}"));
            }
        }

        return view('flute::pages.login', [
            "social" => social()->toDisplay(),
        ])->fragmentIf($request->isOnlyHtmx() && config('auth.only_modal'), 'auth-card');
    }

    public function getRegister(FluteRequest $request)
    {
        breadcrumb()->add(config('app.name'), url('/'))
            ->add(__('auth.header.register'));

        return view('flute::pages.register', [
            "social" => social()->toDisplay(),
        ])->fragmentIf($request->isOnlyHtmx() && config('auth.only_modal'), 'register-card');
    }

    public function getLogout(FluteRequest $request)
    {
        try {
            auth()->logout();

            social()->clearAuthData();

            flash()->success(__('auth.logout_success'));

            return response()->redirect('/');
        } catch (Exception $e) {
            logs()->error($e);
            $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');

            return response()->error(500, $message);
        }
    }

    public function getConfirmation(FluteRequest $request, string $token)
    {
        try {
            auth()->verify($token);
            flash()->add('success', __('auth.confirmation.success'));

            return response()->redirect('/');
        } catch (Exception $e) {
            return response()->error(404, __('auth.confirmation.verify_old'));
        } catch (AccountNotVerifiedException $e) {
            return response()->error(404, __('auth.confirmation.verify_old'));
        }
    }
}
