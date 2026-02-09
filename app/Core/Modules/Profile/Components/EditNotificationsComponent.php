<?php

namespace Flute\Core\Modules\Profile\Components;

use Flute\Core\Database\Entities\NotificationTemplate;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserNotificationSetting;
use Flute\Core\Support\FluteComponent;

class EditNotificationsComponent extends FluteComponent
{
    public ?User $user = null;

    /**
     * Mount the component.
     */
    public function mount()
    {
        $this->user = user()->getCurrentUser();
    }

    /**
     * Render the component view.
     *
     * @return mixed
     */
    public function render()
    {
        $setting = $this->getUserSetting();
        $channelSettings = $setting ? $setting->getChannelSettings() : [];
        $templateSettings = $setting ? $setting->getTemplateSettings() : [];

        $availableChannels = $this->getAvailableChannels();
        $templates = $this->getGroupedTemplates();

        return $this->view('flute::components.profile-tabs.edit.notifications', [
            'user' => $this->user,
            'channelSettings' => $channelSettings,
            'templateSettings' => $templateSettings,
            'availableChannels' => $availableChannels,
            'groupedTemplates' => $templates,
        ]);
    }

    /**
     * Save global channel preferences.
     */
    public function saveChannels()
    {
        $setting = $this->getOrCreateSetting();
        $availableChannels = $this->getAvailableChannels();

        $channels = [];
        foreach ($availableChannels as $channelKey => $channelInfo) {
            $paramName = 'channel_' . $channelKey;
            $value = request()->get($paramName);
            $channels[$channelKey] = $value === '1' || $value === 'on' || $value === true;
        }

        $setting->setChannelSettings($channels);
        $setting->save();

        $this->flashMessage(__('profile.edit.notifications.save_success'), 'success');
    }

    /**
     * Save per-template channel overrides.
     */
    public function saveTemplates()
    {
        $setting = $this->getOrCreateSetting();
        $availableChannels = $this->getAvailableChannels();

        $templates = NotificationTemplate::query()
            ->where(['is_enabled' => true])
            ->fetchAll();

        $templateSettings = [];

        foreach ($templates as $template) {
            $templateChannels = $template->getChannels();
            if (empty($templateChannels)) {
                $templateChannels = ['inapp'];
            }

            $overrides = [];
            foreach ($templateChannels as $channel) {
                if (!isset($availableChannels[$channel])) {
                    continue;
                }

                $paramName = 'tpl_' . str_replace('.', '__', $template->key) . '_' . $channel;
                $value = request()->get($paramName);
                $overrides[$channel] = $value === '1' || $value === 'on' || $value === true;
            }

            if (!empty($overrides)) {
                $templateSettings[$template->key] = $overrides;
            }
        }

        $setting->setTemplateSettings($templateSettings);
        $setting->save();

        $this->flashMessage(__('profile.edit.notifications.save_success'), 'success');
    }

    /**
     * Get the user's notification setting entity.
     */
    protected function getUserSetting(): ?UserNotificationSetting
    {
        return UserNotificationSetting::findOne(['user_id' => $this->user->id]);
    }

    /**
     * Get or create the user's notification setting entity.
     */
    protected function getOrCreateSetting(): UserNotificationSetting
    {
        $setting = $this->getUserSetting();

        if (!$setting) {
            $setting = new UserNotificationSetting();
            $setting->user = $this->user;
        }

        return $setting;
    }

    /**
     * Get available channels that are actually enabled on the platform.
     */
    protected function getAvailableChannels(): array
    {
        $allChannels = [
            'inapp' => [
                'name' => __('profile.edit.notifications.channels.inapp'),
                'icon' => 'ph.bold.bell-bold',
                'description' => __('profile.edit.notifications.channels.inapp_desc'),
            ],
            'email' => [
                'name' => __('profile.edit.notifications.channels.email'),
                'icon' => 'ph.bold.envelope-bold',
                'description' => __('profile.edit.notifications.channels.email_desc'),
            ],
        ];

        // Only show email channel if SMTP is configured
        if (empty(config('mail.smtp')) || empty(config('mail.host'))) {
            unset($allChannels['email']);
        }

        return $allChannels;
    }

    /**
     * Get notification templates grouped by module.
     *
     * @return array<string, NotificationTemplate[]>
     */
    protected function getGroupedTemplates(): array
    {
        $templates = NotificationTemplate::query()
            ->where(['is_enabled' => true])
            ->orderBy('module', 'ASC')
            ->orderBy('priority', 'ASC')
            ->fetchAll();

        $grouped = [];

        foreach ($templates as $template) {
            $module = $template->module ?? 'core';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $template;
        }

        uksort($grouped, static function ($a, $b) {
            if ($a === 'core') {
                return -1;
            }
            if ($b === 'core') {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $grouped;
    }
}
