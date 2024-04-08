<?php

namespace Flute\Core\Admin\Http\Controllers\Api\Payments;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class PaymentsController extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.gateways');
        $this->middleware(HasPermissionMiddleware::class);
        $this->middleware(CSRFMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        if (!payments()->gatewayExists($request->adapter))
            return $this->error(__('admin.payments.gateway_not_exists', ['name' => $request->adapter]), 404);

        if (!$request->additional)
            return $this->error(__('admin.payments.min_one_value'));

        $gateway = rep(PaymentGateway::class)->findOne([
            'adapter' => $request->adapter,
        ]);

        if ($gateway)
            return $this->error(__('admin.payments.exists', ['name' => $request->adapter]), 403);

        $payment = new PaymentGateway;
        $payment->name = $request->name;
        $payment->adapter = $request->adapter;
        $payment->enabled = filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN) ?? false;
        $payment->additional = \Nette\Utils\Json::encode($request->additional);

        transaction($payment)->run();

        return $this->success();
    }

    public function enable(FluteRequest $request, string $id)
    {
        $payment = $this->getPayment((int) $id);

        if (!$payment)
            return $this->error(__('admin.payments.not_found'), 404);

        $payment->enabled = true;

        transaction($payment)->run();

        return $this->success();
    }

    public function disable(FluteRequest $request, string $id)
    {
        $payment = $this->getPayment((int) $id);

        if (!$payment)
            return $this->error(__('admin.payments.not_found'), 404);

        $payment->enabled = false;

        transaction($payment)->run();

        return $this->success();
    }

    public function delete(FluteRequest $request, string $id)
    {
        $payment = $this->getPayment((int) $id);

        if (!$payment)
            return $this->error(__('admin.payments.not_found'), 404);

        transaction($payment, 'delete')->run();

        return $this->success();
    }

    public function edit(FluteRequest $request, string $id)
    {
        if (!payments()->gatewayExists($request->adapter))
            return $this->error(__('admin.payments.gateway_not_exists', ['name' => $request->adapter]), 404);

        if (!$request->additional)
            return $this->error(__('admin.payments.min_one_value'));

        $payment = $this->getPayment((int) $id);

        if (!$payment)
            return $this->error(__('admin.payments.not_found'), 404);

        $payment->name = $request->name;
        $payment->adapter = $request->adapter;
        $payment->enabled = filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN) ?? false;
        $payment->additional = \Nette\Utils\Json::encode($request->additional);

        transaction($payment)->run();

        return $this->success();
    }

    protected function getPayment(int $id)
    {
        return rep(PaymentGateway::class)->findByPK($id);
    }
}