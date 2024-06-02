<?php

namespace Flute\Core\Admin\Http\Controllers\Views\Payments;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;
use Nette\Utils\Strings;
use Omnipay\Common\Helper;
use Symfony\Component\HttpFoundation\Response;

class PaymentsView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.gateways');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function payments()
    {
        $table = table();

        $data = rep(PaymentInvoice::class)->select()->load(['user', 'promoCode', 'currency'])->fetchAll();

        foreach ($data as $item) {
            $item->avatar = $item->user->avatar;
            $item->user_url = url(user()->canEditUser($item->user) ? ('admin/users/edit/' . $item->user->id) : ('profile/' . $item->user->id))->get();

            $item->amountWithCurrency = $item->originalAmount . ' ' . $item->currency->code;
            $item->user_name = $item->user->name;

            $item->promoCode = !empty($item->promoCode) ? $item->promoCode->code : __('def.no');

            if ($item->isPaid) {
                $item->paidCard = '<div class="paid-container">
                    <span class="table-status active">' . __('def.paid') . '</span>
                    <small class="paid-at">' . __('admin.payments.paid_at', [
                        ':time' => $item->paidAt->format(default_date_format())
                    ]) . '</small>
                </div>';
            } else {
                $item->paidCard = '<span class="table-status error">' . __('def.not_paid') . '</span>';
            }
        }

        // Добавляем объединенную колонку
        $table->addColumn((new TableColumn('user_url', 'user_url'))->setVisible(false));
        $table->addCombinedColumn('avatar', 'user_name', __('def.user'), 'user_url');
        $table->addColumn(new TableColumn('gateway', __('admin.payments.adapter')));
        $table->addColumn(new TableColumn('transactionId', __('admin.payments.transactionId')));
        $table->addColumn(new TableColumn('amountWithCurrency', __('admin.payments.amount')));
        $table->addColumn(new TableColumn('promoCode', __('admin.payments.promoCode')));
        $table->addColumn((new TableColumn('paidCard', __('admin.payments.isPaid')))->setClean(false));

        $table->setData($data);

        return view("Core/Admin/Http/Views/pages/payments/payments", [
            'payments' => $table->render()
        ]);
    }

    public function list(): Response
    {
        $table = table();
        $payments = rep(PaymentGateway::class)->findAll();

        $table->addColumns([
            (new TableColumn('id', "ID")),
            (new TableColumn('name', __('def.name')))->setType('text'),
            (new TableColumn('adapter', __('admin.payments.adapter')))->setType('text'),
            (new TableColumn('enabled', __('def.status')))->setRender(
                '{{ RENDER_STATUS }}',
                "function(data, type, full, meta) {
                    let div = make('div');
                    let status = data == 1 ? 'active' : 'disabled';
                    div.classList.add('table-status', status);
                    div.innerHTML = translate(`def.`+status)
                    return div;
                }"
            ),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ PAYMENTS_BUTTONS }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[3] == 1 ? "active" : "disabled";
    
                    let btnContainer = make("div");
                    btnContainer.classList.add("payment-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-translate", "admin.payments.delete");
                    deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    deleteDiv.setAttribute("data-deletepayment", data[0]);
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-translate", "admin.payments.change");
                    changeDiv.setAttribute("data-translate-attribute", "data-tooltip");

                    changeDiv.setAttribute("href", u(`admin/payments/edit/${data[0]}`));
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);

                    if (status === "active") {
                        let disableDiv = make("div");
                        disableDiv.classList.add("action-button", "disable");
                        disableDiv.setAttribute("data-translate", "admin.payments.disable_payment");
                        disableDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        disableDiv.setAttribute("data-disablepayment", data[0]);
                        let disableIcon = make("i");
                        disableIcon.classList.add("ph-bold", "ph-power");
                        disableDiv.appendChild(disableIcon);
                        btnContainer.appendChild(disableDiv);
                    }
        
                    // Включить модуль
                    if (status === "disabled") {
                        let activeDiv = make("div");
                        activeDiv.classList.add("action-button", "activate");
                        activeDiv.setAttribute("data-translate", "admin.payments.enable_payment");
                        activeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        activeDiv.setAttribute("data-activatepayment", data[0]);
                        let activeIcon = make("i");
                        activeIcon.classList.add("ph-bold", "ph-power");
                        activeDiv.appendChild(activeIcon);
                        btnContainer.appendChild(activeDiv);
                    }
    
                    return btnContainer.outerHTML;
                }
                '
            ]
        ]);

        $table->setData($payments);

        return view("Core/Admin/Http/Views/pages/payments/list", [
            "payments" => $table->render()
        ]);
    }

    public function add(FluteRequest $request): Response
    {
        return view("Core/Admin/Http/Views/pages/payments/add", [
            'drivers' => $this->getAllDrivers()
        ]);
    }

    public function edit(FluteRequest $request, string $id): Response
    {
        $payment = $this->getPaymentGateway((int) $id);

        if (!$payment)
            return $this->error(__('admin.payments.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/payments/edit", [
            'gateway' => $payment,
            'additional' => \Nette\Utils\Json::decode($payment->additional),
            'drivers' => $this->getAllDrivers()
        ]);
    }

    protected function getAllDrivers()
    {
        $namespaceMap = app()->getLoader()->getPrefixesPsr4();
        $result = [];

        foreach ($namespaceMap as $namespace => $paths) {
            foreach ($paths as $path) {
                if (strpos($namespace, 'Omnipay\\') !== 0) {
                    continue;
                }

                $fullPath = realpath($path);
                if ($fullPath && is_dir($fullPath)) {
                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath));
                    foreach ($files as $file) {
                        if ($file->isFile() && $file->getExtension() == 'php') {
                            $filename = $file->getFilename();
                            if (substr($filename, -11) == 'Gateway.php') {
                                $gatewayClassShortName = substr($filename, 0, -4);
                                $gatewayClass = $namespace . $gatewayClassShortName;


                                if (payments()->gatewayExists($gatewayClass) && !Strings::startsWith($gatewayClassShortName, 'Abstract')) {
                                    $gatewayInstance = new $gatewayClass();

                                    if (is_callable([$gatewayInstance, 'getName'])) {
                                        $driverName = Helper::getGatewayShortName($gatewayClass);
                                        $result[$driverName]['name'] = $gatewayInstance->getName();
                                        $result[$driverName]['parameters'] = $this->getGatewayParameters($gatewayInstance);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function getGatewayParameters($gatewayInstance)
    {
        $parameters = [];
        $reflectionClass = new \ReflectionClass($gatewayInstance);
        $except = ['currency', 'amount', 'transactionId'];

        foreach ($reflectionClass->getMethods() as $method) {
            if (strpos($method->name, 'get') === 0) {
                $methodBody = file_get_contents($method->getFileName());
                $startLine = $method->getStartLine() - 1;
                $endLine = $method->getEndLine();
                $length = $endLine - $startLine;

                $source = implode("\n", array_slice(explode("\n", $methodBody), $startLine, $length));

                if (preg_match('/->getParameter\([\'"]([^\'"]+)[\'"]\)/', $source, $matches)) {
                    if (!in_array($matches[1], $except))
                        $parameters[] = $matches[1];
                }
            }
        }

        return $parameters;
    }


    protected function getPaymentGateway(int $id): ?PaymentGateway
    {
        return rep(PaymentGateway::class)->findByPK($id);
    }
}