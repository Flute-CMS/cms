<?php

namespace Flute\Core\Modules\Notifications\Controllers;

use Flute\Core\Database\Entities\NotificationTemplate;
use Flute\Core\Database\Entities\UserNotificationSetting;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class NotificationSettingsController extends BaseController
{
    /**
     * Get the current user's notification preferences.
     */
    public function getSettings()
    {
        $currentUser = user()->getCurrentUser();
        $setting = UserNotificationSetting::findOne(['user_id' => $currentUser->id]);

        return $this->json([
            'channels' => $setting ? $setting->getChannelSettings() : [],
            'templates' => $setting ? $setting->getTemplateSettings() : [],
        ]);
    }

    /**
     * Save the current user's notification channel preferences.
     */
    public function saveChannelSettings(FluteRequest $request)
    {
        $currentUser = user()->getCurrentUser();
        $setting = UserNotificationSetting::findOne(['user_id' => $currentUser->id]);

        if (!$setting) {
            $setting = new UserNotificationSetting();
            $setting->user = $currentUser;
        }

        $channels = $request->input('channels', []);

        if (!is_array($channels)) {
            return $this->error(__('def.invalid_data'), 422);
        }

        $setting->setChannelSettings($channels);
        $setting->save();

        return $this->success();
    }

    /**
     * Save the current user's per-template notification preferences.
     */
    public function saveTemplateSettings(FluteRequest $request)
    {
        $currentUser = user()->getCurrentUser();
        $setting = UserNotificationSetting::findOne(['user_id' => $currentUser->id]);

        if (!$setting) {
            $setting = new UserNotificationSetting();
            $setting->user = $currentUser;
        }

        $templates = $request->input('templates', []);

        if (!is_array($templates)) {
            return $this->error(__('def.invalid_data'), 422);
        }

        // Validate template keys exist
        foreach (array_keys($templates) as $key) {
            $template = NotificationTemplate::findOne(['key' => $key]);
            if (!$template) {
                return $this->error(__('def.not_found'), 404);
            }
        }

        $setting->setTemplateSettings($templates);
        $setting->save();

        return $this->success();
    }
}
