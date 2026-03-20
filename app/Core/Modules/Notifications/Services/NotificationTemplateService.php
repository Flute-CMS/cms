<?php

namespace Flute\Core\Modules\Notifications\Services;

use Flute\Core\Database\Entities\Notification;
use Flute\Core\Database\Entities\NotificationTemplate;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserNotificationSetting;
use Flute\Core\Modules\Notifications\Contracts\NotificationTemplateProviderInterface;
use InvalidArgumentException;
use Throwable;

/**
 * Service for managing notification templates.
 *
 * Handles template registration, retrieval, and sending notifications
 * based on templates with variable substitution.
 */
class NotificationTemplateService
{
    /**
     * Cache key prefix for templates
     */
    protected const CACHE_PREFIX = 'notification.template.';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * In-memory cache of templates by key
     */
    protected array $templateCache = [];

    /**
     * Registered template providers
     */
    protected array $providers = [];

    /**
     * Get a template by its key.
     */
    public function getByKey(string $key): ?NotificationTemplate
    {
        if (isset($this->templateCache[$key])) {
            return $this->templateCache[$key];
        }

        $cacheKey = self::CACHE_PREFIX . md5($key);

        if (!is_development()) {
            try {
                $cached = cache()->get($cacheKey);
                if ($cached instanceof NotificationTemplate) {
                    $this->templateCache[$key] = $cached;

                    return $cached;
                }
            } catch (Throwable) {
            }
        }

        $template = NotificationTemplate::findOne(['key' => $key]);

        if ($template) {
            $this->templateCache[$key] = $template;

            if (!is_development()) {
                try {
                    cache()->set($cacheKey, $template, self::CACHE_TTL);
                } catch (Throwable) {
                }
            }
        }

        return $template;
    }

    /**
     * Get all templates, optionally filtered by module.
     *
     * @return NotificationTemplate[]
     */
    public function getAll(?string $module = null): array
    {
        $query = NotificationTemplate::query()->orderBy('module', 'ASC')->orderBy('priority', 'ASC');

        if ($module !== null) {
            if ($module === 'core') {
                $query->where(['module' => null]);
            } else {
                $query->where(['module' => $module]);
            }
        }

        return $query->fetchAll();
    }

    /**
     * Get templates grouped by module.
     *
     * @return array<string, NotificationTemplate[]>
     */
    public function getGroupedByModule(): array
    {
        $templates = $this->getAll();
        $grouped = [];

        foreach ($templates as $template) {
            $module = $template->module ?? 'core';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $template;
        }

        // Sort modules: core first, then alphabetically
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

    /**
     * Get unique module names from existing templates.
     *
     * @return string[]
     */
    public function getModules(): array
    {
        $templates = $this->getAll();
        $modules = ['core'];

        foreach ($templates as $template) {
            if ($template->module && !in_array($template->module, $modules, true)) {
                $modules[] = $template->module;
            }
        }

        return $modules;
    }

    /**
     * Send a notification using a template.
     */
    public function send(string $templateKey, User $user, array $data = [], ?array $overrideChannels = null): bool
    {
        $template = $this->getByKey($templateKey);

        // If template not found, try syncing providers first (table might have just been created)
        if (!$template && !empty($this->providers)) {
            try {
                $this->syncAllProviders();
                $template = $this->getByKey($templateKey);
            } catch (Throwable) {
            }
        }

        if (!$template) {
            logs()->warning("Notification template not found: {$templateKey}");

            return false;
        }

        if (!$template->is_enabled) {
            return false;
        }

        $channels = $overrideChannels ?? $template->getChannels();
        if (empty($channels)) {
            $channels = ['inapp'];
        }

        $userSetting = $this->getUserNotificationSetting($user);

        foreach ($channels as $channel) {
            if (!$template->hasChannel($channel) && $overrideChannels === null) {
                continue;
            }

            if ($userSetting && !$userSetting->isTemplateChannelEnabled($templateKey, $channel)) {
                continue;
            }

            try {
                $this->sendToChannel($channel, $template, $user, $data);
            } catch (Throwable $e) {
                logs()->error("Failed to send notification via {$channel}: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Register a template provider.
     */
    public function registerProvider(NotificationTemplateProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Register a single template from array definition.
     */
    public function registerTemplate(array $templateData, ?string $module = null): NotificationTemplate
    {
        $key = $templateData['key'] ?? null;
        if (!$key) {
            throw new InvalidArgumentException('Template key is required');
        }

        $existing = $this->getByKey($key);

        if ($existing) {
            if (!$existing->is_customized) {
                $existing->title = $templateData['title'];
                $existing->content = $templateData['content'];
                $existing->icon = $templateData['icon'] ?? null;
                $existing->layout = $templateData['layout'] ?? 'standard';
                $existing->setVariables($templateData['variables'] ?? null);
                $existing->setComponents($templateData['components'] ?? null);
                $existing->setChannels($templateData['channels'] ?? null);
                $existing->priority = $templateData['priority'] ?? 100;
            }
            $existing->setOriginalData([
                'title' => $templateData['title'],
                'content' => $templateData['content'],
                'icon' => $templateData['icon'] ?? null,
                'layout' => $templateData['layout'] ?? 'standard',
                'components' => $templateData['components'] ?? null,
                'channels' => $templateData['channels'] ?? null,
            ]);
            $existing->save();
            $this->invalidateCache($key);

            return $existing;
        }

        $template = new NotificationTemplate();
        $template->key = $key;
        $template->module = $module;
        $template->title = $templateData['title'];
        $template->content = $templateData['content'];
        $template->icon = $templateData['icon'] ?? null;
        $template->layout = $templateData['layout'] ?? 'standard';
        $template->setVariables($templateData['variables'] ?? null);
        $template->setComponents($templateData['components'] ?? null);
        $template->setChannels($templateData['channels'] ?? null);
        $template->priority = $templateData['priority'] ?? 100;
        $template->setOriginalData([
            'title' => $templateData['title'],
            'content' => $templateData['content'],
            'icon' => $templateData['icon'] ?? null,
            'layout' => $templateData['layout'] ?? 'standard',
            'components' => $templateData['components'] ?? null,
            'channels' => $templateData['channels'] ?? null,
        ]);

        $template->save();
        $this->invalidateCache($key);

        return $template;
    }

    /**
     * Register multiple templates from a provider.
     * Uses cache to avoid DB writes on every request.
     */
    public function registerFromProvider(NotificationTemplateProviderInterface $provider): void
    {
        $module = $provider->getModuleName();
        $templates = $provider->getNotificationTemplates();

        $cacheKey = 'notification.provider.registered.' . md5($module);

        if (!is_development()) {
            try {
                $registered = cache()->get($cacheKey);
                if ($registered === true) {
                    return;
                }
            } catch (Throwable) {
            }
        }

        foreach ($templates as $templateData) {
            try {
                $this->registerTemplate($templateData, $module);
            } catch (Throwable $e) {
                logs()->error("Failed to register template [{$templateData['key']}]: " . $e->getMessage());

                return; // If table doesn't exist, stop trying
            }
        }

        // Mark provider as registered in cache
        if (!is_development()) {
            try {
                cache()->set($cacheKey, true, 86400); // 24 hours
            } catch (Throwable) {
            }
        }
    }

    /**
     * Sync all templates from registered providers.
     */
    public function syncAllProviders(): void
    {
        foreach ($this->providers as $provider) {
            $this->registerFromProvider($provider);
        }
    }

    /**
     * Update a template.
     */
    public function update(NotificationTemplate $template, array $data): void
    {
        if (isset($data['title'])) {
            $template->title = $data['title'];
        }
        if (isset($data['content'])) {
            $template->content = $data['content'];
        }
        if (array_key_exists('icon', $data)) {
            $template->icon = $data['icon'];
        }
        if (isset($data['layout'])) {
            $template->layout = $data['layout'];
        }
        if (array_key_exists('components', $data)) {
            $template->setComponents($data['components']);
        }
        if (array_key_exists('channels', $data)) {
            $template->setChannels($data['channels']);
        }
        if (isset($data['is_enabled'])) {
            $template->is_enabled = (bool) $data['is_enabled'];
        }
        if (isset($data['priority'])) {
            $template->priority = (int) $data['priority'];
        }

        $template->is_customized = true;
        $template->save();

        $this->invalidateCache($template->key);
    }

    /**
     * Reset template to original defaults.
     */
    public function reset(NotificationTemplate $template): void
    {
        $template->resetToDefaults();
        $template->save();
        $this->invalidateCache($template->key);
    }

    /**
     * Toggle template enabled state.
     */
    public function toggle(NotificationTemplate $template): void
    {
        $template->is_enabled = !$template->is_enabled;
        $template->save();
        $this->invalidateCache($template->key);
    }

    /**
     * Delete a template.
     */
    public function delete(NotificationTemplate $template): void
    {
        $key = $template->key;
        $template->delete();
        $this->invalidateCache($key);
    }

    /**
     * Get available component types.
     */
    public function getComponentTypes(): array
    {
        return [
            'text' => [
                'name' => __('admin-notifications.components.text'),
                'icon' => 'ph.bold.text-t-bold',
                'fields' => ['content'],
            ],
            'header' => [
                'name' => __('admin-notifications.components.header'),
                'icon' => 'ph.bold.text-h-bold',
                'fields' => ['icon', 'iconColor', 'badge'],
            ],
            'actions' => [
                'name' => __('admin-notifications.components.actions'),
                'icon' => 'ph.bold.cursor-click-bold',
                'fields' => ['buttons'],
            ],
            'progress' => [
                'name' => __('admin-notifications.components.progress'),
                'icon' => 'ph.bold.chart-bar-bold',
                'fields' => ['current', 'max', 'label'],
            ],
            'rewards' => [
                'name' => __('admin-notifications.components.rewards'),
                'icon' => 'ph.bold.gift-bold',
                'fields' => ['items'],
            ],
            'countdown' => [
                'name' => __('admin-notifications.components.countdown'),
                'icon' => 'ph.bold.timer-bold',
                'fields' => ['expiresAt', 'label'],
            ],
            'code' => [
                'name' => __('admin-notifications.components.code'),
                'icon' => 'ph.bold.code-bold',
                'fields' => ['code', 'copyable'],
            ],
            'image' => [
                'name' => __('admin-notifications.components.image'),
                'icon' => 'ph.bold.image-bold',
                'fields' => ['src', 'alt'],
            ],
            'user' => [
                'name' => __('admin-notifications.components.user'),
                'icon' => 'ph.bold.user-bold',
                'fields' => ['userId', 'showAvatar', 'showName'],
            ],
            'divider' => [
                'name' => __('admin-notifications.components.divider'),
                'icon' => 'ph.bold.minus-bold',
                'fields' => [],
            ],
            'callout' => [
                'name' => __('admin-notifications.components.callout'),
                'icon' => 'ph.bold.info-bold',
                'fields' => ['content', 'type'],
            ],
            'stats' => [
                'name' => __('admin-notifications.components.stats'),
                'icon' => 'ph.bold.chart-line-up-bold',
                'fields' => ['items'],
            ],
        ];
    }

    /**
     * Get available button action types.
     */
    public function getButtonActionTypes(): array
    {
        return [
            'navigate' => __('admin-notifications.actions.navigate'),
            'api' => __('admin-notifications.actions.api'),
            'modal' => __('admin-notifications.actions.modal'),
            'copy' => __('admin-notifications.actions.copy'),
            'download' => __('admin-notifications.actions.download'),
            'dismiss' => __('admin-notifications.actions.dismiss'),
            'external' => __('admin-notifications.actions.external'),
        ];
    }

    /**
     * Get available layout types.
     */
    public function getLayoutTypes(): array
    {
        return [
            'standard' => __('admin-notifications.layouts.standard'),
            'card' => __('admin-notifications.layouts.card'),
            'hero' => __('admin-notifications.layouts.hero'),
            'compact' => __('admin-notifications.layouts.compact'),
        ];
    }

    /**
     * Get available channels.
     */
    public function getChannels(): array
    {
        return [
            'inapp' => [
                'name' => __('admin-notifications.channels.inapp'),
                'icon' => 'ph.bold.bell-bold',
                'enabled' => true,
            ],
            'email' => [
                'name' => __('admin-notifications.channels.email'),
                'icon' => 'ph.bold.envelope-bold',
                'enabled' => function_exists('email'),
            ],
            'telegram' => [
                'name' => __('admin-notifications.channels.telegram'),
                'icon' => 'ph.bold.telegram-logo-bold',
                'enabled' => false, // Placeholder
            ],
            'push' => [
                'name' => __('admin-notifications.channels.push'),
                'icon' => 'ph.bold.device-mobile-bold',
                'enabled' => false, // Placeholder
            ],
        ];
    }

    /**
     * Send notification to a specific channel.
     */
    protected function sendToChannel(string $channel, NotificationTemplate $template, User $user, array $data): void
    {
        switch ($channel) {
            case 'inapp':
                $this->sendInApp($template, $user, $data);

                break;

            case 'email':
                $this->sendEmail($template, $user, $data);

                break;

            case 'telegram':
                $this->sendTelegram($template, $user, $data);

                break;

            case 'push':
                $this->sendPush($template, $user, $data);

                break;

            default:
                logs()->warning("Unknown notification channel: {$channel}");
        }
    }

    /**
     * Send in-app notification.
     */
    protected function sendInApp(NotificationTemplate $template, User $user, array $data): void
    {
        $freshUser = User::findByPK($user->id);
        if (!$freshUser) {
            logs()->warning("Cannot send inapp notification: user #{$user->id} not found");

            return;
        }

        $notification = new Notification();
        $notification->user = $freshUser;
        $notification->title = $template->getParsedTitle($data);
        $notification->content = $template->getParsedContent($data);
        $notification->icon = $template->icon;

        $components = $template->getComponents();
        $parsedComponents = !empty($components) ? $template->getParsedComponents($data) : [];

        if ($components && $this->hasButtons($components)) {
            $notification->type = 'button';
            $notification->setExtraData([
                'buttons' => $this->extractButtons($parsedComponents),
                'components' => $parsedComponents,
                'layout' => $template->layout,
                'template_key' => $template->key,
            ]);
        } else {
            $notification->type = 'text';
            if ($components) {
                $notification->setExtraData([
                    'components' => $parsedComponents,
                    'layout' => $template->layout,
                    'template_key' => $template->key,
                ]);
            }
        }

        $notification->saveOrFail();

        if (function_exists('notification')) {
            try {
                notification()->refresh();
            } catch (Throwable) {
            }
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(NotificationTemplate $template, User $user, array $data): void
    {
        if (!function_exists('email')) {
            return;
        }

        $userEmail = $user->email ?? null;
        if (!$userEmail) {
            return;
        }

        try {
            email()->send(
                $userEmail,
                $template->getParsedTitle($data),
                view('notifications::emails.notification', [
                    'title' => $template->getParsedTitle($data),
                    'content' => $template->getParsedContent($data),
                    'components' => $template->getParsedComponents($data),
                    'user' => $user,
                ]),
            );
        } catch (Throwable $e) {
            logs()->error('Failed to send email notification: ' . $e->getMessage());
        }
    }

    /**
     * Send Telegram notification.
     */
    protected function sendTelegram(NotificationTemplate $template, User $user, array $data): void
    {
        // Implementation depends on Telegram integration
        // This is a placeholder for future implementation
    }

    /**
     * Send push notification.
     */
    protected function sendPush(NotificationTemplate $template, User $user, array $data): void
    {
        // Implementation depends on push notification service
        // This is a placeholder for future implementation
    }

    /**
     * Check if components contain buttons.
     */
    protected function hasButtons(array $components): bool
    {
        foreach ($components as $component) {
            if (is_array($component)) {
                if (( $component['type'] ?? '' ) === 'actions') {
                    return true;
                }
                if ($this->hasButtons($component)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract button definitions from components.
     */
    protected function extractButtons(array $components): array
    {
        $buttons = [];

        foreach ($components as $component) {
            if (is_array($component)) {
                if (( $component['type'] ?? '' ) === 'actions' && isset($component['buttons'])) {
                    $buttons = array_merge($buttons, $component['buttons']);
                } else {
                    $buttons = array_merge($buttons, $this->extractButtons($component));
                }
            }
        }

        return $buttons;
    }

    /**
     * Get user notification setting.
     */
    protected function getUserNotificationSetting(User $user): ?UserNotificationSetting
    {
        try {
            return UserNotificationSetting::findOne(['user_id' => $user->id]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Invalidate cache for a template.
     */
    protected function invalidateCache(string $key): void
    {
        unset($this->templateCache[$key]);

        try {
            cache()->delete(self::CACHE_PREFIX . md5($key));
        } catch (Throwable) {
        }
    }
}
