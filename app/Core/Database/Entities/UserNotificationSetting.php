<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\ORM\Entity\Behavior;


#[Entity]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
#[Index(columns: ['user_id'], unique: true)]
class UserNotificationSetting extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[BelongsTo(target: "User", nullable: false)]
    public User $user;

    /**
     * Global channel preferences: {"email": true, "inapp": true}
     */
    #[Column(type: "text", nullable: true)]
    public ?string $channel_settings = null;

    /**
     * Per-template overrides: {"shop.purchase_success": {"email": false}, "auth.login": {"inapp": false}}
     */
    #[Column(type: "text", nullable: true)]
    public ?string $template_settings = null;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getChannelSettings(): array
    {
        return $this->channel_settings ? json_decode($this->channel_settings, true) : [];
    }

    public function setChannelSettings(array $data): void
    {
        $this->channel_settings = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getTemplateSettings(): array
    {
        return $this->template_settings ? json_decode($this->template_settings, true) : [];
    }

    public function setTemplateSettings(array $data): void
    {
        $this->template_settings = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Check if user has enabled a specific channel globally.
     */
    public function isChannelEnabled(string $channel): bool
    {
        $settings = $this->getChannelSettings();

        return $settings[$channel] ?? true;
    }

    /**
     * Check if user has enabled a specific channel for a specific template.
     */
    public function isTemplateChannelEnabled(string $templateKey, string $channel): bool
    {
        $templateSettings = $this->getTemplateSettings();

        if (isset($templateSettings[$templateKey][$channel])) {
            return (bool) $templateSettings[$templateKey][$channel];
        }

        return $this->isChannelEnabled($channel);
    }
}
