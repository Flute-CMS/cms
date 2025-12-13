@if (config('auth.two_factor.enabled') &&
        config('auth.two_factor.force') &&
        user()->isLoggedIn() &&
        !user()->getCurrentUser()->hasTwoFactorEnabled())
    <div id="two-factor-alert">
        <x-alert type="warning" icon="ph.regular.shield-warning" :withClose="false">
            <div class="two-factor-alert-content">
                <div class="two-factor-alert-text">
                    <strong>{{ __('auth.two_factor.alert_title') }}</strong>
                    <span>{{ __('auth.two_factor.alert_description') }}</span>
                </div>
                <div class="two-factor-alert-actions">
                    <x-button type="warning" size="tiny" href="{{ url('profile/settings#two-factor-settings') }}">
                        <x-icon path="ph.regular.shield-plus" />
                        {{ __('auth.two_factor.alert_enable') }}
                    </x-button>
                    <x-button type="outline-primary" size="tiny" onclick="dismissTwoFactorAlert()">
                        {{ __('auth.two_factor.alert_dismiss') }}
                    </x-button>
                </div>
            </div>
        </x-alert>
    </div>

    <script>
        function dismissTwoFactorAlert() {
            const alert = document.getElementById('two-factor-alert');
            if (alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
                sessionStorage.setItem('2fa_alert_dismissed', 'true');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (sessionStorage.getItem('2fa_alert_dismissed') === 'true') {
                const alert = document.getElementById('two-factor-alert');
                if (alert) alert.remove();
            }
        });
    </script>

    <style>
        #two-factor-alert {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .two-factor-alert-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-md);
            width: 100%;
            flex-wrap: wrap;
        }
        .two-factor-alert-text {
            display: flex;
            flex-direction: column;
            gap: var(--space-2xs);
        }
        .two-factor-alert-text strong {
            font-weight: 600;
        }
        .two-factor-alert-actions {
            display: flex;
            gap: var(--space-xs);
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .two-factor-alert-content {
                flex-direction: column;
                align-items: flex-start;
            }
            .two-factor-alert-actions {
                width: 100%;
            }
        }
    </style>
@endif
