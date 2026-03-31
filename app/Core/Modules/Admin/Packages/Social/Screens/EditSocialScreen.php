<?php

namespace Flute\Admin\Packages\Social\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\SocialNetwork;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Nette\Utils\Json;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class EditSocialScreen extends Screen
{
    public ?string $name = 'admin-social.title.edit';

    public ?string $description = 'admin-social.title.description';

    public ?string $permission = 'admin.socials';

    public ?SocialNetwork $social = null;

    public $driverKey = null;

    public bool $isEditMode = false;

    public ?int $id = null;

    protected array $nonKeySettingFields = ['scope', 'fields', 'display', 'version', 'service_token', 'proxy'];

    protected array $driverIcons = [
        'Steam' => 'fontawesome.brands.steam',
        'HttpsSteam' => 'fontawesome.brands.steam',
        'Discord' => 'fontawesome.brands.discord',
        'Telegram' => 'fontawesome.brands.telegram',
        'Minecraft' => 'fontawesome.brands.xbox',
        'Twitch' => 'fontawesome.brands.twitch',
        'TwitchTV' => 'fontawesome.brands.twitch',
        'Twitter' => 'fontawesome.brands.x-twitter',
        'GitHub' => 'fontawesome.brands.github',
        'GitLab' => 'fontawesome.brands.gitlab',
        'Google' => 'fontawesome.brands.google',
        'Apple' => 'fontawesome.brands.apple',
        'Facebook' => 'fontawesome.brands.facebook',
        'LinkedIn' => 'fontawesome.brands.linkedin',
        'LinkedInOpenID' => 'fontawesome.brands.linkedin',
        'Vkontakte' => 'fontawesome.brands.vk',
        'Yandex' => 'fontawesome.brands.yandex',
        'MicrosoftGraph' => 'fontawesome.brands.microsoft',
        'WindowsLive' => 'fontawesome.brands.microsoft',
        'Spotify' => 'fontawesome.brands.spotify',
        'Reddit' => 'fontawesome.brands.reddit',
        'Patreon' => 'fontawesome.brands.patreon',
        'Instagram' => 'fontawesome.brands.instagram',
        'Yahoo' => 'fontawesome.brands.yahoo',
        'Amazon' => 'fontawesome.brands.amazon',
        'BitBucket' => 'fontawesome.brands.bitbucket',
        'Blizzard' => 'fontawesome.brands.battle-net',
        'BlizzardEU' => 'fontawesome.brands.battle-net',
        'BlizzardAPAC' => 'fontawesome.brands.battle-net',
        'DeviantArt' => 'fontawesome.brands.deviantart',
        'Disqus' => 'fontawesome.brands.discourse',
        'Dribbble' => 'fontawesome.brands.dribbble',
        'Dropbox' => 'fontawesome.brands.dropbox',
        'Foursquare' => 'fontawesome.brands.foursquare',
        'Mastodon' => 'fontawesome.brands.mastodon',
        'Medium' => 'fontawesome.brands.medium',
        'ORCID' => 'fontawesome.brands.orcid',
        'Paypal' => 'fontawesome.brands.paypal',
        'PaypalOpenID' => 'fontawesome.brands.paypal',
        'Pinterest' => 'fontawesome.brands.pinterest',
        'QQ' => 'fontawesome.brands.qq',
        'Slack' => 'fontawesome.brands.slack',
        'StackExchange' => 'fontawesome.brands.stack-exchange',
        'StackExchangeOpenID' => 'fontawesome.brands.stack-exchange',
        'Strava' => 'fontawesome.brands.strava',
        'Tumblr' => 'fontawesome.brands.tumblr',
        'WeChat' => 'fontawesome.brands.weixin',
        'WeChatChina' => 'fontawesome.brands.weixin',
        'WordPress' => 'fontawesome.brands.wordpress',
    ];

    protected $supportedDrivers = [
        'Steam' => 'Steam',
        'Discord' => 'Discord',
        'Telegram' => 'Telegram',
        'Minecraft' => 'Minecraft',
        'Twitch' => 'Twitch',
        'Twitter' => 'Twitter',
        'GitHub' => 'GitHub',
        'GitLab' => 'GitLab',
        'Google' => 'Google',
        'Apple' => 'Apple',
        'Facebook' => 'Facebook',
        'LinkedIn' => 'LinkedIn',
        'Vkontakte' => 'Vkontakte',
        'Yandex' => 'Yandex',
        'MicrosoftGraph' => 'MicrosoftGraph',
        'Spotify' => 'Spotify',
        'Reddit' => 'Reddit',
        'Patreon' => 'Patreon',
        'Instagram' => 'Instagram',
        'Yahoo' => 'Yahoo',
    ];

    public function mount(): void
    {
        $this->id = (int) ( request()->attributes->get('id') ?: request()->input('id') ?: $this->id );

        // Read driverKey from request (sent by Yoyo select on change)
        $requestDriverKey = request()->input('driverKey');
        if (is_array($requestDriverKey)) {
            $requestDriverKey = $requestDriverKey[0] ?? null;
        }

        if ($this->id) {
            $this->social = rep(SocialNetwork::class)->findByPK($this->id);

            if (!$this->social) {
                $this->redirect('/admin/socials');

                return;
            }

            $this->isEditMode = true;

            breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
                __('admin-social.title.social'),
                url('/admin/socials'),
            )->add($this->social->key);

            $this->name = __('admin-social.title.edit', ['name' => $this->social->key]);

            $this->driverKey = $requestDriverKey ?: $this->social->key;
        } else {
            breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
                __('admin-social.title.social'),
                url('/admin/socials'),
            )->add(__('admin-social.title.create'));

            $this->name = __('admin-social.title.create');
            $this->driverKey = $requestDriverKey;
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.cancel'))->redirect('/admin/socials')->type(Color::OUTLINE_PRIMARY),

            Button::make(__('admin-social.buttons.delete'))
                ->method('delete')
                ->type(Color::OUTLINE_DANGER)
                ->confirm(__('admin-social.confirms.delete'))
                ->setVisible($this->isEditMode),

            Button::make(__('admin-social.buttons.save'))->method('save'),
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
            : collect(rep(SocialNetwork::class)->findAll())->pluck('key')->toArray();

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
                                    ->disableFromRequest()
                                    ->value($this->driverIcons[$driverKey] ?? ( $this->social?->icon ?: '' ))
                                    ->placeholder(__('admin-social.fields.icon.placeholder')),
                            )
                                ->label(__('admin-social.fields.icon.label'))
                                ->required(),

                            LayoutFactory::field(
                                ButtonGroup::make('allow_to_register')
                                    ->options([
                                        '0' => ['label' => __('def.no'), 'icon' => 'ph.bold.x-bold'],
                                        '1' => ['label' => __('def.yes'), 'icon' => 'ph.bold.check-bold'],
                                    ])
                                    ->value($this->social?->allowToRegister ?? true ? '1' : '0')
                                    ->color('accent'),
                            )
                                ->label(__('admin-social.fields.allow_register.label'))
                                ->popover(__('admin-social.fields.allow_register.help')),
                        ])->ratio('50/50'),

                        LayoutFactory::field(
                            Input::make('cooldown_time')
                                ->value($this->social->cooldownTime ?? '0')
                                ->placeholder(__('admin-social.fields.cooldown_time.placeholder'))
                                ->required()
                                ->type('number'),
                        )
                            ->label(__('admin-social.fields.cooldown_time.label'))
                            ->small(__('admin-social.fields.cooldown_time.small'))
                            ->popover(__('admin-social.fields.cooldown_time.help')),

                        LayoutFactory::field(
                            Input::make('settings__proxy')
                                ->value($this->social ? $this->social->getSettings()['proxy'] ?? '' : '')
                                ->placeholder(__('admin-social.fields.proxy.placeholder')),
                        )
                            ->label(__('admin-social.fields.proxy.label'))
                            ->small(__('admin-social.fields.proxy.small'))
                            ->popover(__('admin-social.fields.proxy.help')),
                    ])->addClass('mb-3'),

                    $driverKey
                        ? LayoutFactory::block([
                            LayoutFactory::view('admin-social::edit.redirect-uris', [
                                'driverKey' => $driverKey,
                            ]),
                        ])->addClass('mb-3')->morph(false)
                        : null,
                ]),

                LayoutFactory::block($this->getEditDriverFields($availableDrivers, $driverKey))->addClass('mb-3'),
            ]),
        ];
    }

    public function save()
    {
        $data = request()->input();

        unset($data['id']);

        $rules = [
            'icon' => 'required|string|max-str-len:255',
            'allow_to_register' => 'sometimes|boolean',
            'cooldown_time' => 'required|integer|min:0',
            'settings__proxy' => 'sometimes|string|max-str-len:255',
        ];

        if ($this->isEditMode && !isset($data['driverKey'])) {
            $data['driverKey'] = $this->social->key;
        }

        $settings = $this->extractSettingsFromRequest($data);
        $data['settings'] = $settings;

        if ($this->isEditMode || $this->isEditMode === false && Arr::has($data, 'driverKey')) {
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
                $this->social->allowToRegister = filter_var(
                    $data['allow_to_register'] ?? false,
                    FILTER_VALIDATE_BOOLEAN,
                );
                $this->social->cooldownTime = (int) $data['cooldown_time'];

                $this->social->settings = Json::encode($settings);

                $this->social->saveOrFail();
            } else {
                $data['settings'] = Json::encode($settings);
                $data['allowToRegister'] = filter_var($data['allow_to_register'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $data['enabled'] = true;

                SocialNetwork::make($data)->saveOrFail();

                $this->redirectTo('/admin/socials', 300);
            }

            $this->clearSocialCache();

            $this->flashMessage(__('admin-social.messages.save_success'), 'success');
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-social.messages.save_error', ['message' => $e->getMessage()]), 'error');

            return;
        }
    }

    public function delete()
    {
        if (!$this->isEditMode || !$this->social) {
            $this->flashMessage(__('admin-social.messages.not_found'), 'error');

            return;
        }

        try {
            SocialNetwork::findByPK($this->social->id)->delete();

            $this->clearSocialCache();

            $this->flashMessage(__('admin-social.messages.delete_success'), 'success');
            $this->redirectTo('/admin/socials', 300);
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-social.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    /**
     * Получает список всех драйверов из пространства имён HybridAuth\Provider.
     *
     * @return array
     */
    protected function getAllAvailableDrivers()
    {
        return cache()->callback(
            'available_social_drivers',
            function () {
                $namespaceMap = app()->getLoader()->getPrefixesPsr4();
                $result = [];

                foreach ($namespaceMap as $namespace => $paths) {
                    foreach ($paths as $path) {
                        $fullPath = realpath($path);
                        if ($fullPath && is_dir($fullPath)) {
                            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
                            foreach ($files as $file) {
                                if ($file->isFile() && $file->getExtension() == 'php') {
                                    $class =
                                        $namespace
                                        . str_replace('/', '\\', substr($file->getPathname(), strlen($fullPath), -4));
                                    $ex = explode('\\', $class);
                                    $driver = $ex[array_key_last($ex)];

                                    if (
                                        Str::startsWith($class, 'Hybridauth\Provider')
                                        && !Str::contains($class, array_merge(
                                            ['\\Discord', 'HttpsSteam', 'StorageSession'],
                                            array_keys($this->supportedDrivers),
                                        ))
                                    ) {
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
            },
            3600,
        );
    }

    protected function extractSettingsFromRequest(array $data): array
    {
        $settings = ['keys' => []];

        foreach ($data as $key => $value) {
            if (!Str::startsWith($key, 'settings__')) {
                continue;
            }

            $settingKey = Str::after($key, 'settings__');

            if (in_array($settingKey, $this->nonKeySettingFields, true)) {
                $settings[$settingKey] = $value;
            } else {
                $settings['keys'][$settingKey] = $value;
            }
        }

        return $settings;
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
                'settings__secret' => 'sometimes|string|max-str-len:255',
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
            'Minecraft' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'GitLab' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Apple' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Spotify' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Reddit' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            'Patreon' => $rules = array_merge($rules, [
                'settings__id' => 'required|string|max-str-len:255|min-str-len:2',
                'settings__secret' => 'required|string|max-str-len:255',
            ]),
            default => [],
        };

        return $rules;
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
            LayoutFactory::view('admin-social::edit.driver-select', [
                'availableDrivers' => $availableDrivers,
                'driverKey' => $driverKey,
                'driverIcons' => $this->driverIcons,
                'isEditMode' => $this->isEditMode,
            ]),
        ];

        if ($driverKey) {
            $viewName = view()->exists("admin-social::edit.socials.{$driverKey}")
                ? "admin-social::edit.socials.{$driverKey}"
                : 'admin-social::edit.socials.default';

            $fields[] = LayoutFactory::view($viewName, [
                'social' => $this->social,
                'driverKey' => $driverKey,
            ]);
        }

        return $fields;
    }

    /**
     * Clear social network related caches.
     */
    private function clearSocialCache(): void
    {
        try {
            cache()->deleteImmediately('available_social_drivers');
            cache()->deleteImmediately('flute.social_networks');
            cache()->deleteImmediately('flute.global.layout');
        } catch (Throwable $e) {
            // Do not break admin flow if cache clearing fails
        }
    }
}
