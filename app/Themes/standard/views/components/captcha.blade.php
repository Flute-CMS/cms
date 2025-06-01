@php
    $captchaService = app(\Flute\Core\Services\CaptchaService::class);
    $isEnabled = $captchaService->isEnabled($action ?? 'login');
    $type = $captchaService->getType();
    $siteKey = $captchaService->getSiteKey();
    $scriptUrl = $captchaService->getScriptUrl();
@endphp

@if($isEnabled && !empty($siteKey))
    <head>
        <script src="{{ $scriptUrl }}" async defer></script>
    </head>

    <div class="captcha-container mb-3 w-100">
        @if($type === 'recaptcha_v2')
            <div class="g-recaptcha w-100" data-sitekey="{{ $siteKey }}"></div>
        @elseif($type === 'hcaptcha')
            <div class="h-captcha w-100" data-sitekey="{{ $siteKey }}"></div>
        @endif
    </div>
@endif 