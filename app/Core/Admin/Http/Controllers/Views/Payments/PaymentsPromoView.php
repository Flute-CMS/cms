<?php

namespace Flute\Core\Admin\Http\Controllers\Views\Payments;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\PromoCode;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;
use Symfony\Component\HttpFoundation\Response;

class PaymentsPromoView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.gateways');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table();
        $promos = rep(PromoCode::class)->select()->load('usages')->fetchAll();

        foreach ($promos as $promo) {
            $promo->expires_at = $promo->expires_at->format(default_date_format());

            $promo->usages = $promo->usages->count();
        }

        $table->addColumns([
            (new TableColumn('id', "ID")),
            (new TableColumn('code', __('def.name')))->setType('text'),
            (new TableColumn('max_usages', __('admin.payments.promo.max_use'))),
            (new TableColumn('usages', __('admin.payments.promo.max_use'))),
            (new TableColumn('type', __('def.type')))->setRender(
                '{{ RENDER_STATUS }}',
                "function(data, type, full, meta) {
                    let div = make('div');
                    div.innerHTML = translate(`admin.payments.promo.`+data)
                    return div;
                }"
            ),
            (new TableColumn('value', __('def.value'))),
            (new TableColumn('expires_at', __('admin.payments.promo.expires_at'))),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ PROMOS_BUTTONS }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[3] == 1 ? "active" : "disabled";
    
                    let btnContainer = make("div");
                    btnContainer.classList.add("payment-promo-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-translate", "admin.payments.promo.delete");
                    deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    deleteDiv.setAttribute("data-tooltip-conf", "left");
                    deleteDiv.setAttribute("data-deletepromo", data[0]);
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-translate", "admin.payments.promo.change");
                    changeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    changeDiv.setAttribute("data-tooltip-conf", "left");
                    changeDiv.setAttribute("href", u(`admin/payments/promo/edit/${data[0]}`));
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);
    
                    return btnContainer.outerHTML;
                }
                '
            ]
        ]);

        $table->setData($promos);

        return view("Core/Admin/Http/Views/pages/payments/promo/list", [
            "promo" => $table->render()
        ]);
    }

    public function add(FluteRequest $request): Response
    {
        return view("Core/Admin/Http/Views/pages/payments/promo/add");
    }

    public function edit(FluteRequest $request, string $id): Response
    {
        $promo = $this->getPromoCode((int) $id);

        if (!$promo)
            return $this->error(__('admin.payments.promo.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/payments/promo/edit", [
            'promo' => $promo,
        ]);
    }

    protected function getPromoCode(int $id): ?PromoCode
    {
        return rep(PromoCode::class)->findByPK($id);
    }
}