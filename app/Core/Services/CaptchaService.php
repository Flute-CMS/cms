<?php

namespace Flute\Core\Services;

class CaptchaService
{
    /**
     * Check captcha depending on type
     *
     * @param string $response
     * @param string $type
     * @return bool
     */
    public function verify(string $response, string $type): bool
    {
        if (empty($response)) {
            return false;
        }

        switch ($type) {
            case 'recaptcha_v2':
                return $this->verifyRecaptcha($response);
            case 'hcaptcha':
                return $this->verifyHcaptcha($response);
            default:
                return false;
        }
    }

    /**
     * Check reCAPTCHA v2
     *
     * @param string $response
     * @return bool
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
            'remoteip' => request()->getClientIp()
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'timeout' => 10
                ]
            ]);

            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                return false;
            }

            $json = json_decode($result, true);
            
            return isset($json['success']) && $json['success'] === true;
        } catch (\Exception $e) {
            logs()->error('reCAPTCHA verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check hCaptcha
     *
     * @param string $response
     * @return bool
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
            'remoteip' => request()->getClientIp()
        ];

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'timeout' => 10
                ]
            ]);

            $result = file_get_contents($url, false, $context);
            
            if ($result === false) {
                return false;
            }

            $json = json_decode($result, true);
            
            return isset($json['success']) && $json['success'] === true;
        } catch (\Exception $e) {
            logs()->error('hCaptcha verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if captcha is enabled for a specific action
     *
     * @param string $action
     * @return bool
     */
    public function isEnabled(string $action): bool
    {
        return config("auth.captcha.enabled.{$action}", false);
    }

    /**
     * Get captcha type
     *
     * @return string
     */
    public function getType(): string
    {
        return config('auth.captcha.type', 'recaptcha_v2');
    }

    /**
     * Get site key for current captcha type
     *
     * @return string
     */
    public function getSiteKey(): string
    {
        $type = $this->getType();
        
        switch ($type) {
            case 'recaptcha_v2':
                return config('auth.captcha.recaptcha.site_key', '');
            case 'hcaptcha':
                return config('auth.captcha.hcaptcha.site_key', '');
            default:
                return '';
        }
    }

    /**
     * Get script URL for current captcha type
     *
     * @return string
     */
    public function getScriptUrl(): string
    {
        $type = $this->getType();
        
        switch ($type) {
            case 'recaptcha_v2':
                return 'https://www.google.com/recaptcha/api.js';
            case 'hcaptcha':
                return 'https://js.hcaptcha.com/1/api.js';
            default:
                return '';
        }
    }
} 