<?php

namespace Flute\Core\Admin\Http\Controllers\Api\Payments;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class PaymentsPromoController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.gateways');
        $this->middleware(HasPermissionMiddleware::class);
        $this->middleware(CSRFMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        if (payments()->promo()->exists($request->code))
            return $this->error(__('admin.payments.promo.promo_duplicate'));

        $code = new PromoCode;
        $code->code = $request->code;
        $code->max_usages = $request->max_usages;
        $code->type = $request->type;
        $code->value = $request->value;
        $code->expires_at = $request->expires_at;

        transaction($code)->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        $code = $this->getPromoCode((int) $id);

        if (!$code)
            return $this->error(__('admin.payments.promo.not_found'), 404);

        if ($request->code !== $code->code && payments()->promo()->exists($request->code))
            return $this->error(__('admin.payments.promo.promo_duplicate'));

        $code->code = $request->code;
        $code->max_usages = $request->max_usages;
        $code->type = $request->type;
        $code->value = $request->value;
        $code->expires_at = $request->expires_at;

        transaction($code)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $code = $this->getPromoCode((int) $id);

        if (!$code)
            return $this->error(__('admin.payments.promo.not_found'), 404);

        transaction($code, 'delete')->run();

        return $this->success();
    }

    protected function getPromoCode(int $id)
    {
        return rep(PromoCode::class)->findByPK($id);
    }
}