<?php

namespace Flute\Core\Modules\Installer\Services;

use Flute\Core\Services\EncryptService;

class SystemConfiguration
{
    /**
     * Initialize system configuration
     *
     * @return void
     */
    public function initSystem()
    {
        if (!empty(config()->get('app.key'))) {
            return;
        }

        $appKey = $this->generateAppKey();
        $siteUrl = $this->detectSiteUrl();
        $timezone = $this->detectTimezone();

        $this->saveSettings($appKey, $siteUrl, $timezone);
    }

    /**
     * Generate a random application key
     *
     * @return string
     */
    protected function generateAppKey(): string
    {
        return base64_encode(EncryptService::generateKey('aes-256-cbc'));
    }

    /**
     * Detect the site URL
     *
     * @return string
     */
    protected function detectSiteUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol.$host;
    }

    /**
     * Detect the server timezone
     *
     * @return string
     */
    protected function detectTimezone(): string
    {
        $serverTimezone = date_default_timezone_get();

        if ($serverTimezone === 'UTC') {
            $validTimezones = timezone_identifiers_list();

            if (!empty($validTimezones)) {
                return 'Europe/Moscow';
            }
        }

        return $serverTimezone;
    }

    /**
     * Save the system settings
     *
     * @param string $appKey
     * @param string $siteUrl
     * @param string $timezone
     * @return void
     */
    protected function saveSettings(string $appKey, string $siteUrl, string $timezone): void
    {
        config()->set('app.key', $appKey);
        config()->set('app.url', $siteUrl);
        config()->set('app.timezone', $timezone);

        $preferredLanguage = translation()->getPreferredLanguage();

        if (!empty($preferredLanguage)) {
            config()->set('lang.locale', $preferredLanguage);
        }

        config()->save();
    }
}
