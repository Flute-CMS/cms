@php
    $captchaService = app(\Flute\Core\Services\CaptchaService::class);
    $type = $captchaService->getType();
    $siteKey = $captchaService->getSiteKey();
    $scriptUrl = $captchaService->getScriptUrl();

    $enabled = isset($enabled)
        ? filter_var($enabled, FILTER_VALIDATE_BOOLEAN)
        : $captchaService->isEnabled($action ?? 'login');

    $secretKey = match ($type) {
        'recaptcha_v2' => (string) config('auth.captcha.recaptcha.secret_key', ''),
        'recaptcha_v3' => (string) config('auth.captcha.recaptcha_v3.secret_key', ''),
        'hcaptcha' => (string) config('auth.captcha.hcaptcha.secret_key', ''),
        'turnstile' => (string) config('auth.captcha.turnstile.secret_key', ''),
        default => '',
    };

    $isConfigured = !empty($siteKey) && !empty($secretKey) && !empty($scriptUrl);
    $captchaAction = (string) ($action ?? 'login');
    $instanceId = 'captcha_' . uniqid();
@endphp

@if ($enabled && $isConfigured)
    @if (request()->htmx()->isHtmxRequest())
        <script src="{{ $scriptUrl }}" async defer></script>
    @else
        @push('head')
            <script src="{{ $scriptUrl }}" async defer></script>
        @endpush
    @endif

    <div class="captcha-container mb-3 w-100">
        @if ($type === 'recaptcha_v2')
            <div class="g-recaptcha w-100" data-sitekey="{{ $siteKey }}"></div>
        @elseif($type === 'recaptcha_v3')
            <input type="hidden" id="{{ $instanceId }}_token" name="g-recaptcha-response" value="" />
            <script>
                (function() {
                    var input = document.getElementById(@json($instanceId + '_token'));
                    if (!input) return;
                    var form = input.closest('form');
                    if (!form) return;

                    if (form.dataset.captchaV3Attached === '1') return;
                    form.dataset.captchaV3Attached = '1';

                    form.addEventListener('submit', function(e) {
                        if (input.value) return;
                        if (form.dataset.captchaV3InFlight === '1') return;

                        e.preventDefault();
                        form.dataset.captchaV3InFlight = '1';

                        var siteKey = @json($siteKey);
                        var action = @json($captchaAction);

                        try {
                            if (!window.grecaptcha || !window.grecaptcha.ready) {
                                form.dataset.captchaV3InFlight = '0';
                                form.submit();
                                return;
                            }

                            window.grecaptcha.ready(function() {
                                window.grecaptcha.execute(siteKey, {
                                        action: action
                                    })
                                    .then(function(token) {
                                        input.value = token || '';
                                        form.dataset.captchaV3InFlight = '0';
                                        form.submit();
                                    })
                                    .catch(function() {
                                        form.dataset.captchaV3InFlight = '0';
                                        form.submit();
                                    });
                            });
                        } catch (err) {
                            form.dataset.captchaV3InFlight = '0';
                            form.submit();
                        }
                    });
                })();
            </script>
        @elseif($type === 'hcaptcha')
            <div class="h-captcha w-100" data-sitekey="{{ $siteKey }}"></div>
        @elseif($type === 'turnstile')
            <div class="cf-turnstile w-100" data-sitekey="{{ $siteKey }}"></div>
        @endif
    </div>
@endif
