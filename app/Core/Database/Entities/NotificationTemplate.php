<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;

#[Entity]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
#[Index(columns: ['key'], unique: true)]
#[Index(columns: ['module'])]
#[Index(columns: ['is_enabled'])]
class NotificationTemplate extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    /**
     * Unique template key, e.g. 'shop.purchase_success', 'battlepass.level_up'
     */
    #[Column(type: "string", nullable: false)]
    public string $key;

    /**
     * Module that registered this template (null = core)
     */
    #[Column(type: "string", nullable: true)]
    public ?string $module = null;

    /**
     * Notification title with variable placeholders, e.g. "Order #{order_id} confirmed"
     */
    #[Column(type: "string", nullable: false)]
    public string $title;

    /**
     * Notification content with variable placeholders
     */
    #[Column(type: "text", nullable: false)]
    public string $content;

    /**
     * Icon class (Phosphor icons), e.g. "ph.bold.shopping-cart-bold"
     */
    #[Column(type: "string", nullable: true)]
    public ?string $icon = null;

    /**
     * Notification layout type: standard, card, hero, compact
     */
    #[Column(type: "string", default: "standard")]
    public string $layout = 'standard';

    /**
     * Available template variables for substitution
     * Format: ['variable_name' => 'description', ...]
     */
    #[Column(type: "text", nullable: true)]
    public ?string $variables = null;

    /**
     * Rich notification components (buttons, progress bars, etc.)
     * Format: array of component definitions
     */
    #[Column(type: "text", nullable: true)]
    public ?string $components = null;

    /**
     * Delivery channels: ['inapp', 'email', 'telegram', 'push']
     */
    #[Column(type: "text", nullable: true)]
    public ?string $channels = null;

    /**
     * Whether this template is active
     */
    #[Column(type: "boolean", default: true)]
    public bool $is_enabled = true;

    /**
     * Whether this template was customized by admin (not using module defaults)
     */
    #[Column(type: "boolean", default: false)]
    public bool $is_customized = false;

    /**
     * Original template data from module (for reset functionality)
     */
    #[Column(type: "text", nullable: true)]
    public ?string $original_data = null;

    /**
     * Template priority for ordering (lower = higher priority)
     */
    #[Column(type: "integer", default: 100)]
    public int $priority = 100;

    #[Column(type: "datetime")]
    public DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    public function getVariables(): array
    {
        return $this->variables ? json_decode($this->variables, true) : [];
    }

    public function setVariables(?array $data): void
    {
        $this->variables = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getComponents(): array
    {
        return $this->components ? json_decode($this->components, true) : [];
    }

    public function setComponents(?array $data): void
    {
        $this->components = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getChannels(): array
    {
        return $this->channels ? json_decode($this->channels, true) : [];
    }

    public function setChannels(?array $data): void
    {
        $this->channels = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getOriginalData(): array
    {
        return $this->original_data ? json_decode($this->original_data, true) : [];
    }

    public function setOriginalData(?array $data): void
    {
        $this->original_data = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
    }

    /**
     * Get parsed title with variables substituted
     */
    public function getParsedTitle(array $data = []): string
    {
        return $this->parseTemplate($this->title, $data);
    }

    /**
     * Get parsed content with variables substituted
     */
    public function getParsedContent(array $data = []): string
    {
        return $this->parseTemplate($this->content, $data);
    }

    /**
     * Get parsed components with variables substituted
     */
    public function getParsedComponents(array $data = []): array
    {
        $components = $this->getComponents();
        if (empty($components)) {
            return [];
        }

        return $this->parseComponentsRecursive($components, $data);
    }

    /**
     * Check if template has specific channel enabled
     */
    public function hasChannel(string $channel): bool
    {
        $channels = $this->getChannels();
        if (empty($channels)) {
            return $channel === 'inapp'; // Default to inapp only
        }

        return in_array($channel, $channels, true);
    }

    /**
     * Reset template to original module defaults
     */
    public function resetToDefaults(): void
    {
        $original = $this->getOriginalData();
        if (empty($original)) {
            return;
        }

        $this->title = $original['title'] ?? $this->title;
        $this->content = $original['content'] ?? $this->content;
        $this->icon = $original['icon'] ?? $this->icon;
        $this->layout = $original['layout'] ?? 'standard';
        $this->setComponents($original['components'] ?? null);
        $this->setChannels($original['channels'] ?? null);
        $this->is_customized = false;
    }

    /**
     * Parse template string with variable substitution.
     * If the template string is an i18n key, it will be translated first.
     */
    protected function parseTemplate(string $template, array $data): string
    {
        if (function_exists('__') && !str_contains($template, ' ') && str_contains($template, '.')) {
            $translated = __($template);
            if ($translated !== $template) {
                $template = $translated;
            }
        }

        return preg_replace_callback('/\{(\w+)\}/', static function ($matches) use ($data) {
            $key = $matches[1];

            return $data[$key] ?? $matches[0];
        }, $template);
    }

    /**
     * Recursively parse components with variable substitution and i18n translation
     */
    protected function parseComponentsRecursive(array $components, array $data): array
    {
        $result = [];

        foreach ($components as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->parseComponentsRecursive($value, $data);
            } elseif (is_string($value)) {
                $translated = $value;
                if (function_exists('__') && !str_contains($value, ' ') && str_contains($value, '.')) {
                    $t = __($value);
                    if ($t !== $value) {
                        $translated = $t;
                    }
                }
                $result[$key] = $this->parseTemplate($translated, $data);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
