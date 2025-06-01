<?php

namespace Flute\Core\Modules\Auth\Controllers;

use Flute\Core\Exceptions\PasswordResetTokenNotFoundException;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class PasswordResetController extends BaseController
{
    public function getReset(FluteRequest $request)
    {
        breadcrumb()->add(config('app.name'), url('/'))
            ->add(__('auth.header.reset'));

        return view('flute::pages.reset');
    }

    public function getResetWithToken(FluteRequest $request, string $token)
    {
        try {
            breadcrumb()->add(config('app.name'), url('/'))
                ->add(__('auth.header.reset'));

            return view('flute::pages.reset-token', [
                'token' => $token
            ]);
        } catch (PasswordResetTokenNotFoundException $e) {
            return $this->error(__('auth.reset.token_not_found'), 404);
        }
    }
}