<?php

namespace Flute\Admin\Packages\Payment\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Modules\Payments\Factories\PaymentDriverFactory;
use Flute\Admin\Packages\Payment\Services\PaymentService;
use Flute\Core\Database\Entities\Currency;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Core\Support\FileUploader;
use League\Glide\Manipulators\Filter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EditPaymentGatewayScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.payments';

    private PaymentService $paymentService;
    private PaymentDriverFactory $driverFactory;
    public ?PaymentGateway $gateway = null;
    public $driverKey = null;
    public bool $isEditMode = false;
    protected $id = null;
    protected ?array $availableDrivers = null;

    public function mount(): void
    {
        $this->paymentService = app(PaymentService::class);
        $this->driverFactory = app(PaymentDriverFactory::class);

        $this->id = (int) request()->input('id');

        if ($this->id) {
            $this->gateway = $this->paymentService->getGatewayById($this->id);

            if (!$this->gateway) {
                $this->redirect('/admin/payment/gateways');
                return;
            }

            $this->isEditMode = true;
            $this->driverKey = $this->gateway->adapter;

            breadcrumb()
                ->add(__('def.admin_panel'), url('/admin'))
                ->add(__('admin-payment.title.gateways'), url('/admin/payment/gateways'))
                ->add($this->gateway->name);

            $this->name = __('admin-payment.title.gateway_edit', ['name' => $this->gateway->name]);
        } else {
            breadcrumb()
                ->add(__('def.admin_panel'), url('/admin'))
                ->add(__('admin-payment.title.gateways'), url('/admin/payment/gateways'))
                ->add(__('admin-payment.title.gateway_add'));

            $this->name = __('admin-payment.title.gateway_add');
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.cancel'))
                ->redirect('/admin/payment/gateways')
                ->type(Color::OUTLINE_PRIMARY),

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

        if (!$this->isEditMode && empty($availableDrivers)) {
            return [
                LayoutFactory::view('admin-payment::edit.no_drivers'),
            ];
        }

        
        $currencyCheckboxes = [];
        foreach ($currencies as $currency) {
            $isChecked = $this->gateway ? $currency->hasPayment($this->gateway) : false;
            $currencyCheckboxes[] = LayoutFactory::field(
                CheckBox::make("currencies.{$currency->id}")
                    ->label($currency->code)
                    ->checked(filter_var(request()->input("currencies_{$currency->id}", $isChecked), FILTER_VALIDATE_BOOLEAN))
            );
        }

        return [
            LayoutFactory::columns([
                LayoutFactory::blank([
                    LayoutFactory::block([
                        LayoutFactory::field(
                            Input::make('name')
                                ->required()
                                ->value($this->gateway->name ?? '')
                                ->placeholder(__('admin-payment.fields.name.placeholder'))
                        )->label(__('admin-payment.fields.name.label'))->required(),

                        LayoutFactory::field(
                            Input::make('image')
                                ->type('file')
                                ->filePond()
                                ->accept('image/png, image/jpeg, image/gif, image/webp')
                                ->defaultFile(asset($this->gateway?->image ?? ''))
                        )->label(__('admin-payment.fields.image.label')),

                        LayoutFactory::field(
                            Toggle::make('enabled')
                                ->checked($this->gateway->enabled ?? true)
                        )->label(__('admin-payment.fields.enabled.label'))->popover(__('admin-payment.fields.enabled.help')),
                    ])->addClass('mb-2'),

                    LayoutFactory::block($currencyCheckboxes)
                        ->title(__('admin-payment.fields.currencies.title'))
                        ->description(__('admin-payment.fields.currencies.description'))
                        ->addClass('mb-4'),

                    LayoutFactory::block([
                        LayoutFactory::field(
                            Input::make('method')
                                ->type('text')
                                ->value("POST")
                                ->readonly()
                        )->label(__('admin-payment.fields.method.label')),
                        LayoutFactory::field(
                            Input::make('handle_url')
                                ->type('text')
                                ->value(url('api/lk/handle/' . $this->gateway->adapter))
                                ->readonly()
                        )->label(__('admin-payment.fields.handle_url.label')),
                        LayoutFactory::field(
                            Input::make('success_url')
                                ->type('text')
                                ->value(url('lk/success'))
                                ->readonly()
                        )->label(__('admin-payment.fields.success_url.label')),
                        LayoutFactory::field(
                            Input::make('fail_url')
                                ->type('text')
                                ->value(url('lk/fail'))
                                ->readonly()
                        )->label(__('admin-payment.fields.fail_url.label')),
                    ]),
                ]),

                LayoutFactory::block(
                    $this->getDriverFields($availableDrivers, $driverKey)
                )->addClass('mb-3'),
            ]),
        ];
    }

    private function getDriverFields(array $availableDrivers, ?string $driverKey = null)
    {
        if (empty($availableDrivers)) {
            return LayoutFactory::view('admin-payment::edit.no_drivers');
        }

        $fields = [
            LayoutFactory::field(
                Select::make('driverKey')
                    ->options($availableDrivers)
                    ->allowEmpty()
                    ->value($driverKey ?? null)
                    ->disabled($this->isEditMode)
                    ->yoyo()
                    ->placeholder(__('admin-payment.fields.payment_system.placeholder'))
                    ->required()
            )->label(__('admin-payment.fields.payment_system.label'))->required()
        ];

        if ($driverKey && $this->driverFactory->hasDriver($driverKey)) {
            $driver = $this->driverFactory->make($driverKey);
            $settingsView = $driver->getSettingsView();

            if (view()->exists($settingsView)) {
                $fields[] = LayoutFactory::view($settingsView, [
                    'gateway' => $this->gateway,
                    'settings' => $this->gateway ? json_decode($this->gateway->additional, true) : []
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
                    throw new \RuntimeException(__('admin-payment.messages.image_upload_error'));
                }

                return $newFile;
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function save()
    {
        $data = request()->input();
        $files = request()->files;

        $selectedCurrencies = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'currencies_')) {
                $currencyId = (int) substr($key, strlen('currencies_'));
                $selectedCurrencies[] = $currencyId;
            }
        }
        $data['currencies'] = $selectedCurrencies;

        if (!$this->validate($this->getValidationRules($data), $data)) {
            return;
        }

        try {
            if ($this->isEditMode) {
                $this->gateway->name = $data['name'];
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

                $gateway->additional = json_encode(['keys' => $settings]);

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

                $this->flashMessage(__('admin-payment.messages.gateway_added'), 'success');
                $this->redirectTo('/admin/payment/gateways', 300);
            }
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-payment.messages.save_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    private function getValidationRules(array $data): array
    {
        $rules = [
            'name' => ['required', 'string', 'max-str-len:255'],
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

    public function delete()
    {
        if (!$this->isEditMode || !$this->gateway) {
            $this->flashMessage(__('admin-payment.messages.gateway_not_found'), 'error');
            return;
        }

        try {
            $this->paymentService->deleteGateway($this->gateway);
            $this->flashMessage(__('admin-payment.messages.gateway_deleted'), 'success');
            $this->redirectTo('/admin/payment/gateways', 300);
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-payment.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }
    }
}
