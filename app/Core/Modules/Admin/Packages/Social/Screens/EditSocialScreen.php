<?php

namespace Flute\Admin\Packages\Social\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\SocialNetwork;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Nette\Utils\Json;

class EditSocialScreen extends Screen
{
    public ?string $name = 'admin-social.title.edit';
    public ?string $description = 'admin-social.title.description';
    public ?string $permission = 'admin.socials';

    public ?SocialNetwork $social = null;
    public $driverKey = null;
    public bool $isEditMode = false;
    protected $id = null;

    protected $supportedDrivers = [
        'Steam' => 'Steam',
        'Discord' => 'Discord',
        'Telegram' => 'Telegram',
        'Twitch' => 'Twitch',
        'Twitter' => 'Twitter',
        'GitHub' => 'GitHub',
        'Google' => 'Google',
        'Facebook' => 'Facebook',
        'LinkedIn' => 'LinkedIn',
        'Vkontakte' => 'Vkontakte',
        'Yandex' => 'Yandex',
        'MicrosoftGraph' => 'MicrosoftGraph',
        'Instagram' => 'Instagram',
        'Yahoo' => 'Yahoo',
    ];

    public function mount(): void
    {
        $this->id = (int) request()->attributes->get('id');

        if ($this->id) {
            $this->social = rep(SocialNetwork::class)->findByPK($this->id);

            if (!$this->social) {
                $this->redirect('/admin/socials');

                return;
            }

            $this->isEditMode = true;

            breadcrumb()
                ->add(__('def.admin_panel'), url('/admin'))
                ->add(__('admin-social.title.social'), url('/admin/socials'))
                ->add($this->social->key);

            $this->name = __('admin-social.title.edit', ['name' => $this->social->key]);

            if (!$this->driverKey) {
                $this->driverKey = $this->social->key;
            }

        } else {
            breadcrumb()
                ->add(__('def.admin_panel'), url('/admin'))
                ->add(__('admin-social.title.social'), url('/admin/socials'))
                ->add(__('admin-social.title.create'));

            $this->name = __('admin-social.title.create');
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.cancel'))
                ->redirect('/admin/socials')
                ->type(Color::OUTLINE_PRIMARY),

            Button::make(__('admin-social.buttons.delete'))
                ->method('delete')
                ->type(Color::OUTLINE_DANGER)
                ->confirm(__('admin-social.confirms.delete'))
                ->setVisible($this->isEditMode),

            Button::make(__('admin-social.buttons.save'))
                ->method('save'),
        ];
    }

    public function layout(): array
    {
        $driverKey = $this->driverKey;

        $allDrivers = array_merge($this->supportedDrivers, $this->getAllAvailableDrivers());

        $registeredDrivers = $this->isEditMode
            ? collect(rep(SocialNetwork::class)->findAll())
                ->where('key', '!=', $this->social->key)
                ->pluck('key')
                ->toArray()
            : collect(rep(SocialNetwork::class)->findAll())
                ->pluck('key')
                ->toArray();

        $availableDrivers = array_diff($allDrivers, array_combine($registeredDrivers, $registeredDrivers));

        if (!$this->isEditMode && empty($availableDrivers)) {
            return [
                LayoutFactory::view('admin-social::edit.no_drivers'),
            ];
        }

        return [
            LayoutFactory::columns([
                LayoutFactory::blank([
                    LayoutFactory::block([
                        LayoutFactory::split([
                            LayoutFactory::field(
                                Input::make('icon')
                                    ->required()
                                    ->type('icon')
                                    ->value($this->social->icon ?? '')
                                    ->placeholder(__('admin-social.fields.icon.placeholder'))
                            )->label(__('admin-social.fields.icon.label'))->required(),

                            LayoutFactory::field(
                                Toggle::make('allow_to_register')
                                    ->checked($this->social->allowToRegister ?? true)
                            )->label(__('admin-social.fields.allow_register.label'))->popover(__('admin-social.fields.allow_register.help')),
                        ])->ratio('50/50'),

                        LayoutFactory::field(
                            Input::make('cooldown_time')
                                ->value($this->social->cooldownTime ?? '0')
                                ->placeholder(__('admin-social.fields.cooldown_time.placeholder'))
                                ->required()
                                ->type('number')
                        )->label(__('admin-social.fields.cooldown_time.label'))->small(__('admin-social.fields.cooldown_time.small'))
                            ->popover(__('admin-social.fields.cooldown_time.help')),
                    ])->addClass('mb-3'),

                    $driverKey ? LayoutFactory::block([
                        LayoutFactory::field(
                            Input::make('redirect_uri_1')
                                ->readonly()
                                ->disableFromRequest()
                                ->value(url('/social/' . $driverKey))
                        )->label(__('admin-social.fields.redirect_uri.first')),
                        LayoutFactory::field(
                            Input::make('redirect_uri_2')
                                ->readonly()
                                ->disableFromRequest()
                                ->value(url('/profile/social/bind/' . $driverKey))
                        )->label(__('admin-social.fields.redirect_uri.second')),
                    ])->addClass('mb-3')->morph(false) : null,
                ]),

                LayoutFactory::block(
                    $this->getEditDriverFields($availableDrivers, $driverKey)
                )->addClass('mb-3'),
            ]),
        ];
    }

    /**
     * Получает поля для режима изменения
     */
    private function getEditDriverFields(array $availableDrivers, ?string $driverKey = null)
    {
        if (!$availableDrivers) {
            return LayoutFactory::view('admin-social::edit.no_drivers');
        }

        $fields = [
            LayoutFactory::field(
                Select::make('driverKey')
                    ->options($availableDrivers)
                    ->allowEmpty()
                    ->value($driverKey ?? null)
                    ->yoyo()
                    ->placeholder(__('admin-social.fields.driver.placeholder'))
                    ->required()
            )->label(__('admin-social.fields.driver.label'))->required(),
        ];

        if ($driverKey) {
            if (view()->exists("admin-social::edit.socials.{$driverKey}")) {
                $fields[] = LayoutFactory::view("admin-social::edit.socials.{$driverKey}", ['social' => $this->social]);
            } else {
                $fields[] = LayoutFactory::blank([
                    LayoutFactory::view("admin-social::edit.socials.default", ['driverKey' => $driverKey]),

                    LayoutFactory::field(
                        Input::make('settings__id')
                            ->required()
                            ->value($this->isEditMode ? $this->social->getSettings()['id'] : '')
                    )->label(__('admin-social.fields.client_id.label')),

                    LayoutFactory::field(
                        Input::make('settings__secret')
                            ->required()
                            ->value($this->isEditMode ? $this->social->getSettings()['secret'] : '')
                    )->label(__('admin-social.fields.client_secret.label')),
                ]);
            }
        }

        return $fields;
    }

    /**
     * Получает список всех драйверов из пространства имён HybridAuth\Provider.
     *
     * @return array
     */
    protected function getAllAvailableDrivers()
    {
        return cache()->callback('available_social_drivers', function () {
            $namespaceMap = app()->getLoader()->getPrefixesPsr4();
            $result = [];

            foreach ($namespaceMap as $namespace => $paths) {
                foreach ($paths as $path) {
                    $fullPath = realpath($path);
                    if ($fullPath && is_dir($fullPath)) {
                        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath));
                        foreach ($files as $file) {
                            if ($file->isFile() && $file->getExtension() == 'php') {
                                $class = $namespace . str_replace('/', '\\', substr($file->getPathname(), strlen($fullPath), -4));
                                $ex = explode('\\', $class);
                                $driver = $ex[array_key_last($ex)];

                                if (Str::startsWith($class, 'Hybridauth\Provider') && !Str::contains($class, ['\\Discord', 'HttpsSteam', 'StorageSession', $this->supportedDrivers])) {
                                    $result[$driver] = $driver;
                                }
                            }
                        }
                    }
                }
            }

            $namespaces = array_keys(app()->getLoader()->getClassMap());
            foreach ($namespaces as $item) {
                if (Str::startsWith($item, "Hybridauth\\Provider")) {
                    $ex = explode('\\', $item);
                    $driver = $ex[array_key_last($ex)];
                    $result[$driver] = $driver;
                }
            }

            return $result;
        }, 3600);
    }

    public function save()
    {
        $data = request()->input();

        unset($data['id']);

        $rules = [
            'icon' => 'required|string|max-str-len:255',
            'allow_to_register' => 'sometimes|boolean',
            'cooldown_time' => 'required|integer|min:0',
        ];

        $settings = [];
        foreach ($data as $key => $value) {
            if (Str::startsWith($key, 'settings__')) {
                $settingKey = Str::after($key, 'settings__');
                $settings[$settingKey] = $value;
            }
        }

        $data['settings'] = $settings;

        if ($this->isEditMode || ($this->isEditMode === false && Arr::has($data, 'driverKey'))) {
            $rules = array_merge($rules, [
                'driverKey' => 'required|string',
            ]);

            if (!isset($this->supportedDrivers[$data['driverKey']])) {
                $rules = array_merge($rules, [
                    'settings__id' => 'required|string|max-str-len:255',
                    'settings__secret' => 'required|string|max-str-len:255',
                ]);
            }

            $rules = $this->mergeWithDefaultRules($data['driverKey'], $rules);
        }

        if (!$this->validate($rules, $data)) {
            return;
        }

        $data['key'] = $data['driverKey'];

        try {
            if ($this->isEditMode) {
                $this->social->icon = $data['icon'];
                $this->social->allowToRegister = filter_var($data['allow_to_register'], FILTER_VALIDATE_BOOLEAN);
                $this->social->cooldownTime = (int) $data['cooldown_time'];

                $this->social->settings = Json::encode(["keys" => $settings]);

                $this->social->saveOrFail();
            } else {
                $data['settings'] = Json::encode(["keys" => $settings]);
                $data['allowToRegister'] = filter_var($data['allow_to_register'], FILTER_VALIDATE_BOOLEAN);
                $data['enabled'] = true;

                SocialNetwork::make($data)->saveOrFail();

                $this->redirectTo('/admin/socials', 300);
            }

            $this->flashMessage(__('admin-social.messages.save_success'), 'success');
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-social.messages.save_error', ['message' => $e->getMessage()]), 'error');

            return;
        }
    }

    protected function mergeWithDefaultRules(string $driverKey, array $rules)
    {
        match ($driverKey) {
            'Discord' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
                'settings__token' => 'sometimes|string',
                'settings__guild_id' => 'sometimes|string',
                'settings__roles_map' => 'sometimes|array',
            ]),
            'Telegram' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Twitch' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Twitter' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'GitHub' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Google' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Facebook' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'LinkedIn' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Vkontakte' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:1',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Yandex' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'MicrosoftGraph' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Instagram' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Yahoo' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            default => [],
        };

        return $rules;
    }

    public function delete()
    {
        if (!$this->isEditMode || !$this->social) {
            $this->flashMessage(__('admin-social.messages.not_found'), 'error');

            return;
        }

        try {
            SocialNetwork::findByPK($this->social->id)->delete();

            $this->flashMessage(__('admin-social.messages.delete_success'), 'success');
            $this->redirectTo('/admin/socials', 300);
        } catch (\Exception $e) {
            $this->flashMessage(__('admin-social.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }
    }
}
