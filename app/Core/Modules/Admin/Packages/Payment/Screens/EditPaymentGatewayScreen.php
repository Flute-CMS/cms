<?php

namespace Flute\Admin\Packages\Payment\Screens;

use Exception;
use Flute\Admin\Packages\Payment\Services\PaymentService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Currency;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Modules\Payments\Factories\PaymentDriverFactory;
use Flute\Core\Support\FileUploader;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EditPaymentGatewayScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.payments';

    public ?PaymentGateway $gateway = null;

    public $driverKey = null;

    public bool $isEditMode = false;

    protected $id = null;

    protected ?array $availableDrivers = null;

    private PaymentService $paymentService;

    private PaymentDriverFactory $driverFactory;

    public function mount(): void
    {
        $this->paymentService = app(PaymentService::class);
        $this->driverFactory = app(PaymentDriverFactory::class);

        $this->id = (int) request()->input('id');

        // Read driverKey from request (sent by Yoyo select on change)
        $requestDriverKey = request()->input('driverKey');
        if (is_array($requestDriverKey)) {
            $requestDriverKey = $requestDriverKey[0] ?? null;
        }

        if ($this->id) {
            $this->gateway = $this->paymentService->getGatewayById($this->id);

            if (!$this->gateway) {
                $this->redirect('/admin/payment/gateways');

                return;
            }

            $this->isEditMode = true;
            $this->driverKey = $this->gateway->adapter;

            breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
                __('admin-payment.title.gateways'),
                url('/admin/payment/gateways'),
            )->add($this->gateway->name);

            $this->name = __('admin-payment.title.gateway_edit', ['name' => $this->gateway->name]);
        } else {
            breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
                __('admin-payment.title.gateways'),
                url('/admin/payment/gateways'),
            )->add(__('admin-payment.title.gateway_add'));

            $this->name = __('admin-payment.title.gateway_add');
            $this->driverKey = $requestDriverKey;
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.cancel'))->redirect('/admin/payment/gateways')->type(Color::OUTLINE_PRIMARY),

            Button::make(__('admin-payment.buttons.delete'))
                ->method('delete')
                ->type(Color::OUTLINE_DANGER)
                ->confirm(__('admin-payment.confirms.delete_gateway'))
                ->setVisible($this->isEditMode),

            Button::make(__('admin-payment.buttons.save'))
                ->method('save')
                ->setVisible(count($this->getAvailableDrivers()) > 0)
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold'),
        ];
    }

    public function layout(): array
    {
        $driverKey = $this->driverKey;
        $availableDrivers = $this->getAvailableDrivers();
        $currencies = rep(Currency::class)->findAll();

        if (empty($currencies)) {
            return [
                LayoutFactory::alert(__('admin-payment.messages.no_currencies'))
                    ->type('warning')
                    ->icon('ph.bold.warning-bold')
                    ->button(__('admin-payment.buttons.create_currency'), url('/admin/currencies/add')),
            ];
        }

        if (!$this->isEditMode && empty($availableDrivers)) {
            return [
                LayoutFactory::view('admin-payment::edit.no_drivers'),
            ];
        }

        $currencyOptions = [];
        $selectedCurrencies = [];
        foreach ($currencies as $currency) {
            $currencyOptions[$currency->id] = $currency->code;
            if ($this->gateway && $currency->hasPayment($this->gateway)) {
                $selectedCurrencies[] = $currency->id;
            }
        }

        $selectedCurrencies = request()->input('currencies', $selectedCurrencies);

        return [
            LayoutFactory::columns([
                LayoutFactory::blank([
                    LayoutFactory::block([
                        LayoutFactory::field(
                            Input::make('name')
                                ->required()
                                ->value($this->gateway->name ?? '')
                                ->placeholder(__('admin-payment.fields.name.placeholder')),
                        )
                            ->label(__('admin-payment.fields.name.label'))
                            ->required()
                            ->small(__('admin-payment.fields.name.help')),

                        LayoutFactory::field(
                            Input::make('image')
                                ->type('file')
                                ->filePond()
                                ->accept('image/png, image/jpeg, image/gif, image/webp')
                                ->defaultFile(asset($this->gateway?->image ?? '')),
                        )->label(__('admin-payment.fields.image.label')),

                        LayoutFactory::field(
                            Input::make('description')
                                ->type('text')
                                ->value($this->gateway->description ?? '')
                                ->placeholder(__('admin-payment.fields.description.placeholder')),
                        )
                            ->label(__('admin-payment.fields.description.label'))
                            ->popover(__('admin-payment.fields.description.help')),

                        LayoutFactory::field(
                            Input::make('fee')
                                ->type('number')
                                ->step('0.01')
                                ->min('0')
                                ->max('100')
                                ->value($this->gateway->fee ?? 0)
                                ->placeholder(__('admin-payment.fields.fee.placeholder')),
                        )
                            ->label(__('admin-payment.fields.fee.label'))
                            ->popover(__('admin-payment.fields.fee.help')),

                        LayoutFactory::field(
                            Input::make('bonus')
                                ->type('number')
                                ->step('0.01')
                                ->min('0')
                                ->max('100')
                                ->value($this->gateway->bonus ?? 0)
                                ->placeholder(__('admin-payment.fields.bonus.placeholder')),
                        )
                            ->label(__('admin-payment.fields.bonus.label'))
                            ->popover(__('admin-payment.fields.bonus.help')),

                        LayoutFactory::field(
                            Input::make('minimum_amount')
                                ->type('number')
                                ->step('0.01')
                                ->min('0')
                                ->value($this->gateway->minimumAmount ?? '')
                                ->placeholder(__('admin-payment.fields.minimum_amount.placeholder')),
                        )
                            ->label(__('admin-payment.fields.minimum_amount.label'))
                            ->popover(__('admin-payment.fields.minimum_amount.help')),

                        LayoutFactory::field(
                            ButtonGroup::make('enabled')
                                ->options([
                                    '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                                    '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                                ])
                                ->value($this->gateway?->enabled ?? true ? '1' : '0')
                                ->color('accent'),
                        )
                            ->label(__('admin-payment.fields.enabled.label'))
                            ->popover(__('admin-payment.fields.enabled.help')),
                    ])->addClass('mb-2'),

                    LayoutFactory::block([
                        LayoutFactory::field(
                            Select::make('currencies')
                                ->options($currencyOptions)
                                ->value($selectedCurrencies)
                                ->multiple()
                                ->required(),
                        )
                            ->label(__('admin-payment.fields.currencies.title'))
                            ->small(__('admin-payment.fields.currencies.description')),
                    ])->addClass('mb-4'),

                    LayoutFactory::block([
                        LayoutFactory::view('admin-payment::edit.gateway-urls', [
                            'handleUrl' => url('api/lk/handle/' . ( $driverKey ?? '' )),
                            'successUrl' => url('lk/success'),
                            'failUrl' => url('lk/fail'),
                        ]),
                    ])->setVisible(!empty($driverKey)),
                ]),

                LayoutFactory::block($this->getDriverFields($availableDrivers, $driverKey))->addClass('mb-3'),
            ]),
        ];
    }

    public function save()
    {
        $data = request()->input();
        $files = request()->files;

        $data['currencies'] = array_map('intval', (array) ( $data['currencies'] ?? [] ));

        if (!$this->validate($this->getValidationRules($data), $data)) {
            return;
        }

        try {
            if ($this->isEditMode) {
                $this->gateway->name = $data['name'];
                $this->gateway->description = $data['description'] ?? null;
                $this->gateway->fee = isset($data['fee']) ? (float) $data['fee'] : 0;
                $this->gateway->bonus = isset($data['bonus']) ? (float) $data['bonus'] : 0;
                $this->gateway->minimumAmount = !empty($data['minimum_amount'])
                    ? (float) $data['minimum_amount']
                    : null;
                $this->gateway->enabled = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN);

                $imageFile = $files->get('image');
                if ($imageFile instanceof UploadedFile) {
                    $newImage = $this->processImageUpload($imageFile);
                    if ($newImage) {
                        $this->gateway->image = $newImage;
                    } else {
                        $this->flashMessage(__('admin-payment.messages.image_upload_error'), 'error');

                        return;
                    }
                }

                if (isset($data['currencies'])) {
                    $currentCurrencies = rep(Currency::class)->findAll();
                    foreach ($currentCurrencies as $currency) {
                        if ($currency->hasPayment($this->gateway)) {
                            $currency->removePayment($this->gateway);
                            $currency->save();
                        }
                    }

                    foreach ($data['currencies'] as $currencyId) {
                        $currency = Currency::findByPK($currencyId);
                        if ($currency) {
                            $currency->addPayment($this->gateway);
                            $currency->save();
                        }
                    }
                }

                if ($this->driverFactory->hasDriver($this->gateway->adapter)) {
                    $driver = $this->driverFactory->make($this->gateway->adapter);
                    $settings = $this->extractDriverSettings($data);

                    if (!validator()->validate($driver->getValidationRules(), $settings)) {
                        $this->flashMessage(__('admin-payment.messages.invalid_payment_settings'), 'error');

                        return;
                    }

                    $this->gateway->setSettings($settings);
                }

                $this->gateway->saveOrFail();

                cache()->delete('flute.payment_gateways');
                cache()->delete('flute.currencies');

                $this->flashMessage(__('admin-payment.messages.gateway_updated'), 'success');
            } else {
                if (!isset($data['driverKey']) || !$this->driverFactory->hasDriver($data['driverKey'])) {
                    $this->flashMessage(__('admin-payment.messages.select_payment_system'), 'error');

                    return;
                }

                $driver = $this->driverFactory->make($data['driverKey']);
                $settings = $this->extractDriverSettings($data);

                if (!validator()->validate($driver->getValidationRules(), $settings)) {
                    $this->flashMessage(__('admin-payment.messages.invalid_payment_settings'), 'error');

                    return;
                }

                $gateway = new PaymentGateway();
                $gateway->name = $data['name'];
                $gateway->description = $data['description'] ?? null;
                $gateway->fee = isset($data['fee']) ? (float) $data['fee'] : 0;
                $gateway->bonus = isset($data['bonus']) ? (float) $data['bonus'] : 0;
                $gateway->minimumAmount = !empty($data['minimum_amount']) ? (float) $data['minimum_amount'] : null;
                $gateway->adapter = $data['driverKey'];
                $gateway->enabled = filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN);

                $imageFile = $files->get('image');
                if ($imageFile instanceof UploadedFile) {
                    $newImage = $this->processImageUpload($imageFile);
                    if ($newImage) {
                        $gateway->image = $newImage;
                    } else {
                        $this->flashMessage(__('admin-payment.messages.image_upload_error'), 'error');

                        return;
                    }
                }

                $gateway->setSettings($settings);

                $gateway->saveOrFail();

                if (isset($data['currencies'])) {
                    foreach ($data['currencies'] as $currencyId) {
                        $currency = Currency::findByPK($currencyId);
                        if ($currency) {
                            $currency->addPayment($gateway);
                            $currency->save();
                        }
                    }
                }

                $gateway->saveOrFail();

                cache()->delete('flute.payment_gateways');
                cache()->delete('flute.currencies');

                $this->flashMessage(__('admin-payment.messages.gateway_added'), 'success');
                $this->redirectTo('/admin/payment/gateways', 300);
            }
        } catch (Exception $e) {
            $this->flashMessage(__('admin-payment.messages.save_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    public function delete()
    {
        if (!$this->isEditMode || !$this->gateway) {
            $this->flashMessage(__('admin-payment.messages.gateway_not_found'), 'error');

            return;
        }

        try {
            $this->paymentService->deleteGateway($this->gateway);

            cache()->delete('flute.payment_gateways');
            cache()->delete('flute.currencies');

            $this->flashMessage(__('admin-payment.messages.gateway_deleted'), 'success');
            $this->redirectTo('/admin/payment/gateways', 300);
        } catch (Exception $e) {
            $this->flashMessage(__('admin-payment.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    private function getDriverFields(array $availableDrivers, ?string $driverKey = null)
    {
        if (empty($availableDrivers)) {
            return LayoutFactory::view('admin-payment::edit.no_drivers');
        }

        $fields = [];

        if ($this->isEditMode) {
            $fields[] = LayoutFactory::field(
                Input::make('driverKey_display')
                    ->readonly()
                    ->disableFromRequest()
                    ->value($driverKey),
            )
                ->label(__('admin-payment.fields.payment_system.label'))
                ->small(__('admin-payment.fields.payment_system.help'));
        } else {
            $fields[] = LayoutFactory::field(
                Select::make('driverKey')
                    ->options($availableDrivers)
                    ->allowEmpty()
                    ->value($driverKey ?? null)
                    ->yoyo()
                    ->placeholder(__('admin-payment.fields.payment_system.placeholder'))
                    ->required(),
            )
                ->label(__('admin-payment.fields.payment_system.label'))
                ->required()
                ->small(__('admin-payment.fields.payment_system.help'));
        }

        if ($driverKey && $this->driverFactory->hasDriver($driverKey)) {
            $driver = $this->driverFactory->make($driverKey);
            $settingsView = $driver->getSettingsView();

            if (view()->exists($settingsView)) {
                $fields[] = LayoutFactory::view($settingsView, [
                    'gateway' => $this->gateway,
                    'settings' => $this->gateway ? json_decode($this->gateway->additional, true) : [],
                ]);
            }
        }

        return $fields;
    }

    private function getAvailableDrivers(): array
    {
        if ($this->availableDrivers !== null) {
            return $this->availableDrivers;
        }

        $registeredDrivers = $this->driverFactory->getDrivers();
        $result = [];

        foreach ($registeredDrivers as $key => $driverClass) {
            if ($this->isEditMode && $this->gateway && $key !== $this->gateway->adapter) {
                continue;
            }

            $gateway = PaymentGateway::findOne(['adapter' => $key]);

            if (!$this->isEditMode && $gateway) {
                continue;
            }

            $driver = $this->driverFactory->make($key);
            $result[$key] = $driver->getName();
        }

        $this->availableDrivers = $result;

        return $result;
    }

    private function processImageUpload(UploadedFile $file): ?string
    {
        if ($file->isValid()) {
            try {
                /** @var FileUploader $uploader */
                $uploader = app(FileUploader::class);
                $newFile = $uploader->uploadImage($file, 10);

                if ($newFile === null) {
                    throw new RuntimeException(__('admin-payment.messages.image_upload_error'));
                }

                return $newFile;
            } catch (Exception $e) {
                return null;
            }
        }

        return null;
    }

    private function getValidationRules(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max-str-len:255'],
            'description' => ['nullable', 'string', 'max-str-len:500'],
            'fee' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'bonus' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'minimum_amount' => ['nullable', 'gte:0'],
            'enabled' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max-file-size:10'],
            'currencies' => ['required', 'array'],
            'currencies.*' => ['required', 'integer', 'exists:currencies,id'],
        ];

        if (!$this->isEditMode) {
            $rules['driverKey'] = ['required', 'string'];
        }

        if (isset($data['driverKey']) && $this->driverFactory->hasDriver($data['driverKey'])) {
            $driver = $this->driverFactory->make($data['driverKey']);
            $rules = array_merge($rules, $driver->getValidationRules());
        }

        return $rules;
    }

    private function extractDriverSettings(array $data): array
    {
        $settings = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'settings__')) {
                $settingKey = substr($key, 10);
                $settings[$settingKey] = $value;
            }
        }

        if (isset($settings['testMode'])) {
            $settings['testMode'] = filter_var($settings['testMode'], FILTER_VALIDATE_BOOLEAN) ?? false;
        }

        return $settings;
    }
}
