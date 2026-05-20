<?php

namespace Flute\Core\Template\Concerns;

trait RemoteAssets
{
    protected function processRemoteAsset(string $url, string $type): string
    {
        if ($this->isLocalUrl($url)) {
            return $this->generateTag($url, $type);
        }

        $localUrl = $this->processCdnAsset($url, $type);

        if ($localUrl === '') {
            $safeUrl = $this->sanitizeRemoteUrl($url);

            return $safeUrl === '' ? '' : $this->generateTag($safeUrl, $type);
        }

        return $this->generateTag($localUrl, $type);
    }

    protected function isLocalUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $parsedAppUrl = parse_url($this->appUrl);

        if (isset($parsedUrl['host'], $parsedAppUrl['host'])) {
            return $parsedUrl['host'] === $parsedAppUrl['host'];
        }

        return true;
    }

    protected function processCdnAsset(string $url, string $type = 'js'): string
    {
        $normalizedUrl = $this->sanitizeRemoteUrl($url);
        if ($normalizedUrl === '') {
            return '';
        }

        if (!$this->isBasicRemoteUrlSafe($normalizedUrl)) {
            return '';
        }

        $allowedExtensions = [
            'css' => ['css'],
            'js' => ['js', 'mjs'],
            'img' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        ];

        $type = array_key_exists($type, $allowedExtensions) ? $type : 'js';
        $path = parse_url($normalizedUrl, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedForType = $allowedExtensions[$type];

        if ($extension === '') {
            $extension = $allowedForType[0];
        } elseif (!in_array($extension, $allowedForType, true)) {
            return $normalizedUrl;
        }

        $hash = sha1($normalizedUrl);
        $localPath = "assets/{$type}/cache/{$hash}.{$extension}";
        $fullLocalPath = BASE_PATH . 'public/' . $localPath;

        if (!file_exists($fullLocalPath)) {
            $content = $this->fetchRemoteAsset($normalizedUrl);
            if ($content === null) {
                logs('templates')->warning('Failed to fetch remote asset: ' . $normalizedUrl);

                return '';
            }
            $this->saveAsset($fullLocalPath, $content);
        }

        $version = filemtime($fullLocalPath);

        return url($localPath) . "?v={$version}";
    }

    private function isBasicRemoteUrlSafe(string $url): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return false;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parsed['host'] ?? '');

        if ($host === '' || $host === 'localhost') {
            return false;
        }

        if (!$this->isRemoteAssetHostAllowed($host)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ips = gethostbynamel($host);
        if ($ips === false || $ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    private function fetchRemoteAsset(string $url): ?string
    {
        $options = [
            'timeout' => $this->remoteAssetTimeout,
            'follow_location' => 0,
            'max_redirects' => 0,
            'ignore_errors' => false,
        ];

        $context = stream_context_create([
            'http' => $options,
            'https' => $options,
        ]);

        $handle = @fopen($url, 'rb', false, $context);
        if ($handle === false) {
            return null;
        }

        try {
            $meta = stream_get_meta_data($handle);
            $headers = $meta['wrapper_data'] ?? [];
            if (!$this->remoteAssetResponseIsOk(is_array($headers) ? $headers : [])) {
                return null;
            }

            $content = stream_get_contents($handle, $this->remoteAssetMaxBytes + 1);
            if ($content === false || strlen($content) > $this->remoteAssetMaxBytes) {
                return null;
            }

            return $content;
        } finally {
            fclose($handle);
        }
    }

    private function remoteAssetResponseIsOk(array $headers): bool
    {
        foreach ($headers as $header) {
            if (!is_string($header) || !preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $matches)) {
                continue;
            }

            $status = (int) $matches[1];

            return $status >= 200 && $status < 300;
        }

        return true;
    }

    private function isRemoteAssetHostAllowed(string $host): bool
    {
        $allowedHosts = config('assets.remote_asset_hosts', []);
        if (!is_array($allowedHosts) || $allowedHosts === []) {
            return true;
        }

        foreach ($allowedHosts as $allowedHost) {
            if (!is_string($allowedHost) || $allowedHost === '') {
                continue;
            }

            if (str_contains($allowedHost, '://')) {
                $parsed = parse_url($allowedHost);
                $allowedHost = (string) ( $parsed['host'] ?? '' );
            }

            if (strtolower($allowedHost) === strtolower($host)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeRemoteUrl(string $url): string
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return '';
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = $parsed['host'] ?? '';
        if ($host === '') {
            return '';
        }

        if (isset($parsed['user']) || isset($parsed['pass'])) {
            return '';
        }

        $port = isset($parsed['port']) ? ':' . (int) $parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . strtolower($host) . $port . $path . $query . $fragment;
    }
}
