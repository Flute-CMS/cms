<?php

namespace Flute\Core\Services;

class CaptchaService
{
    /**
     * Check captcha depending on type
     */
    public function verify(string $response, string $type): bool
    {
        if (empty($response)) {
            return false;
        }

        $action = '';
        if (str_contains($type, ':')) {
            [$type, $action] = explode(':', $type, 2);
        }

        switch ($type) {
            case 'recaptcha_v2':
                return $this->verifyRecaptcha($response);
            case 'recaptcha_v3':
                return $this->verifyRecaptchaV3($response, $action);
            case 'hcaptcha':
                return $this->verifyHcaptcha($response);
            case 'turnstile':
                return $this->verifyTurnstile($response);
            case 'yandex':
                return $this->verifyYandex($response);
            default:
                return false;
        }
    }

    /**
     * Check if captcha is enabled for a specific action
     */
    public function isEnabled(string $action): bool
    {
        return config("auth.captcha.enabled.{$action}", false);
    }

    /**
     * Get captcha type
     */
    public function getType(): string
    {
        return config('auth.captcha.type', 'recaptcha_v2');
    }

    /**
     * Get site key for current captcha type
     */
    public function getSiteKey(): string
    {
        $type = $this->getType();

        switch ($type) {
            case 'recaptcha_v2':
                return config('auth.captcha.recaptcha.site_key', '');
            case 'recaptcha_v3':
                return config('auth.captcha.recaptcha_v3.site_key', '');
            case 'hcaptcha':
                return config('auth.captcha.hcaptcha.site_key', '');
            case 'turnstile':
                return config('auth.captcha.turnstile.site_key', '');
            case 'yandex':
                return config('auth.captcha.yandex.client_key', '');
            default:
                return '';
        }
    }

    /**
     * Get script URL for current captcha type
     */
    public function getScriptUrl(): string
    {
        $type = $this->getType();

        switch ($type) {
            case 'recaptcha_v2':
                return 'https://www.google.com/recaptcha/api.js';
            case 'recaptcha_v3':
                $siteKey = $this->getSiteKey();
                if (empty($siteKey)) {
                    return '';
                }

                return 'https://www.google.com/recaptcha/api.js?render=' . urlencode($siteKey);
            case 'hcaptcha':
                return 'https://js.hcaptcha.com/1/api.js';
            case 'turnstile':
                return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
            case 'yandex':
                return 'https://smartcaptcha.cloud.yandex.ru/captcha.js';
            default:
                return '';
        }
    }

    /**
     * Check reCAPTCHA v2
     */
    protected function verifyRecaptcha(string $response): bool
    {
        $secretKey = config('auth.captcha.recaptcha.secret_key');

        if (empty($secretKey)) {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => request()->ip(),
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'User-Agent: Flute-CMS/1.0',
                    ],
                    'content' => http_build_query($data),
                    'timeout' => 5,
                ],
            ]);

            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                logs()->error('reCAPTCHA verification failed: Unable to reach verification service');

                return false;
            }

            $json = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                logs()->error('reCAPTCHA verification failed: Invalid JSON response');

                return false;
            }

            return isset($json['success']) && $json['success'] === true;
        } catch (Throwable $e) {
            logs()->error('reCAPTCHA verification failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check reCAPTCHA v3
     */
    protected function verifyRecaptchaV3(string $response, string $expectedAction): bool
    {
        $secretKey = config('auth.captcha.recaptcha_v3.secret_key');

        if (empty($secretKey)) {
            return false;
        }

        $threshold = (float) config('auth.captcha.recaptcha_v3.score_threshold', 0.5);
        $threshold = max(0.0, min(1.0, $threshold));

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => request()->ip(),
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'User-Agent: Flute-CMS/1.0',
                    ],
                    'content' => http_build_query($data),
                    'timeout' => 5,
                ],
            ]);

            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                logs()->error('reCAPTCHA v3 verification failed: Unable to reach verification service');

                return false;
            }

            $json = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                logs()->error('reCAPTCHA v3 verification failed: Invalid JSON response');

                return false;
            }

            if (!isset($json['success']) || $json['success'] !== true) {
                return false;
            }

            if (isset($json['score']) && (float) $json['score'] < $threshold) {
                return false;
            }

            return !(
                !empty($expectedAction)
                && isset($json['action'])
                && (string) $json['action'] !== $expectedAction
            );
        } catch (Throwable $e) {
            logs()->error('reCAPTCHA v3 verification failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check hCaptcha
     */
    protected function verifyHcaptcha(string $response): bool
    {
        $secretKey = config('auth.captcha.hcaptcha.secret_key');

        if (empty($secretKey)) {
            return false;
        }

        $url = 'https://hcaptcha.com/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => request()->ip(),
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'User-Agent: Flute-CMS/1.0',
                    ],
                    'content' => http_build_query($data),
                    'timeout' => 5,
                ],
            ]);

            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                logs()->error('hCaptcha verification failed: Unable to reach verification service');

                return false;
            }

            $json = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                logs()->error('hCaptcha verification failed: Invalid JSON response');

                return false;
            }

            return isset($json['success']) && $json['success'] === true;
        } catch (Throwable $e) {
            logs()->error('hCaptcha verification failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check Cloudflare Turnstile
     */
    protected function verifyTurnstile(string $response): bool
    {
        $secretKey = config('auth.captcha.turnstile.secret_key');

        if (empty($secretKey)) {
            return false;
        }

        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => request()->ip(),
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'User-Agent: Flute-CMS/1.0',
                    ],
                    'content' => http_build_query($data),
                    'timeout' => 5,
                ],
            ]);

            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                logs()->error('Turnstile verification failed: Unable to reach verification service');

                return false;
            }

            $json = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                logs()->error('Turnstile verification failed: Invalid JSON response');

                return false;
            }

            return isset($json['success']) && $json['success'] === true;
        } catch (Throwable $e) {
            logs()->error('Turnstile verification failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check Yandex SmartCaptcha
     */
    protected function verifyYandex(string $response): bool
    {
        $secretKey = config('auth.captcha.yandex.server_key');

        if (empty($secretKey)) {
            return false;
        }

        $url = 'https://smartcaptcha.cloud.yandex.ru/validate';
        $data = [
            'secret' => $secretKey,
            'token' => $response,
            'ip' => request()->ip(),
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/x-www-form-urlencoded',
                        'User-Agent: Flute-CMS/1.0',
                    ],
                    'content' => http_build_query($data),
                    'timeout' => 5,
                ],
            ]);

            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                logs()->error('Yandex SmartCaptcha verification failed: Unable to reach verification service');

                return false;
            }

            $json = json_decode($result, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                logs()->error('Yandex SmartCaptcha verification failed: Invalid JSON response');

                return false;
            }

            return isset($json['status']) && $json['status'] === 'ok';
        } catch (Throwable $e) {
            logs()->error('Yandex SmartCaptcha verification failed: ' . $e->getMessage());

            return false;
        }
    }
}
