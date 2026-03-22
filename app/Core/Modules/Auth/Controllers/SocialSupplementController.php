<?php

namespace Flute\Core\Modules\Auth\Controllers;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class SocialSupplementController extends BaseController
{
    public function getPage(FluteRequest $request)
    {
        $encrypted = session()->get('social_supplement');
        if (!$encrypted) {
            flash()->error(__('auth.supplement.session_expired'));

            return response()->redirect('/login');
        }

        try {
            $data = json_decode(encrypt()->decrypt($encrypted), true);
        } catch (\Throwable $e) {
            session()->remove('social_supplement');
            flash()->error(__('auth.supplement.session_expired'));

            return response()->redirect('/login');
        }

        if (empty($data['issued_at']) || ( time() - $data['issued_at'] ) > 900) {
            session()->remove('social_supplement');
            flash()->error(__('auth.supplement.session_expired'));

            return response()->redirect('/login');
        }

        $socialId = $data['social_id'] ?? null;
        $social = $socialId ? SocialNetwork::findByPK($socialId) : null;

        breadcrumb()->add(config('app.name'), url('/'))->add(__('auth.supplement.header'));

        return view('flute::pages.social-supplement', [
            'providerKey' => $social?->key ?? $data['provider_key'] ?? '',
            'providerIcon' => $social?->icon ?? '',
        ]);
    }
}
