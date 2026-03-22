<?php

namespace Flute\Core\Database\Entities;

use Cycle\ActiveRecord\ActiveRecord;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;
use Cycle\Annotated\Annotation\Table;
use Cycle\ORM\Entity\Behavior;

#[Entity]
#[Table(
    indexes: [
        new Index(columns: ["key"], unique: true)
    ]
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class SocialNetwork extends ActiveRecord
{
    #[Column(type: "primary")]
    public int $id;

    #[Column(type: "string")]
    public string $key;

    #[Column(type: "text")]
    public string $settings;

    #[Column(type: "integer", default: 0)]
    public int $cooldownTime = 0;

    #[Column(type: "boolean", default: true)]
    public bool $allowToRegister;

    #[Column(type: "text")]
    public string $icon; // svg or png or icon

    #[Column(type: "boolean", default: false)]
    public bool $enabled;

    #[Column(type: "datetime")]
    public \DateTimeImmutable $createdAt;

    #[Column(type: "datetime", nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function getSettings() : array
    {
        if (empty($this->settings)) {
            return [];
        }

        $decoded = json_decode($this->settings, true) ?? [];
        $keys = $decoded['keys'] ?? [];

        if (isset($decoded['keys'])) {
            unset($decoded['keys']);
        }

        return array_merge($keys, $decoded);
    }

    /**
     * Get the proxy URL from provider settings, or null if not set.
     */
    public function getProxy(): ?string
    {
        $settings = json_decode($this->settings, true) ?? [];

        return !empty($settings['proxy']) ? $settings['proxy'] : null;
    }

    /**
     * Get Guzzle-compatible proxy config array.
     * Returns [] if no proxy is configured.
     */
    public function getGuzzleProxyConfig(): array
    {
        $proxy = $this->getProxy();

        if ($proxy === null) {
            return [];
        }

        return ['proxy' => $proxy];
    }

    /**
     * Get curl options array for proxy.
     * Returns [] if no proxy is configured.
     */
    public function getCurlProxyOptions(): array
    {
        $proxy = $this->getProxy();

        if ($proxy === null) {
            return [];
        }

        $parsed = parse_url($proxy);
        $options = [];

        if (!empty($parsed['user'])) {
            $credentials = $parsed['user'];
            if (!empty($parsed['pass'])) {
                $credentials .= ':' . $parsed['pass'];
            }
            $options[CURLOPT_PROXYUSERPWD] = $credentials;

            $scheme = !empty($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
            $host = $parsed['host'] ?? '';
            $port = !empty($parsed['port']) ? ':' . $parsed['port'] : '';
            $proxy = $scheme . $host . $port;
        }

        $options[CURLOPT_PROXY] = $proxy;

        return $options;
    }
}