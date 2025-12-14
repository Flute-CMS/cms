<?php

namespace Flute\Core\Modules\Profile\Components;

use Flute\Core\Database\Entities\User;
use Flute\Core\Services\TwoFactorService;
use Flute\Core\Support\FluteComponent;

class TwoFactorComponent extends FluteComponent
{
    public ?User $user = null;

    public ?string $verificationCode = null;

    public bool $showSetup = false;

    public bool $showRecoveryCodes = false;

    protected TwoFactorService $twoFactorService;

    protected array $excludesVariables = ['tempSecret', 'tempRecoveryCodes'];

    public function mount()
    {
        $this->user = user()->getCurrentUser();
        $this->twoFactorService = app(TwoFactorService::class);

        $this->showSetup = (bool) session()->get('2fa_setup_active', false);
        $this->showRecoveryCodes = (bool) session()->get('2fa_show_recovery', false);
    }

    public function render()
    {
        $isEnabled = $this->user->hasTwoFactorEnabled();
        $tempSecret = session()->get('2fa_temp_secret');
        $tempRecoveryCodes = session()->get('2fa_temp_codes');

        return $this->view('flute::components.profile-tabs.edit.two-factor', [
            'user' => $this->user,
            'isEnabled' => $isEnabled,
            'showSetup' => $this->showSetup && $tempSecret,
            'tempSecret' => $tempSecret,
            'tempRecoveryCodes' => $tempRecoveryCodes,
            'showRecoveryCodes' => $this->showRecoveryCodes && $tempRecoveryCodes,
            'qrCodeUri' => $tempSecret ? $this->twoFactorService->getQrCodeUri($this->user, $tempSecret) : null,
        ]);
    }

    public function startSetup()
    {
        $this->twoFactorService = app(TwoFactorService::class);

        $tempSecret = $this->twoFactorService->generateSecretKey();
        $tempRecoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        session()->set('2fa_temp_secret', $tempSecret);
        session()->set('2fa_temp_codes', $tempRecoveryCodes);
        session()->set('2fa_setup_active', true);

        $this->showSetup = true;
    }

    public function cancelSetup()
    {
        $this->clearSession();
        $this->showSetup = false;
        $this->verificationCode = null;
    }

    public function confirmSetup()
    {
        $this->twoFactorService = app(TwoFactorService::class);

        $tempSecret = session()->get('2fa_temp_secret');
        $tempRecoveryCodes = session()->get('2fa_temp_codes');

        if (!$tempSecret || !$tempRecoveryCodes) {
            $this->showSetup = false;
            $this->clearSession();

            return;
        }

        if (!$this->validate([
            'verificationCode' => 'required|string|min-str-len:6|max-str-len:6',
        ])) {
            return;
        }

        if (!$this->twoFactorService->verifyCodeWithSecret($tempSecret, $this->verificationCode)) {
            $this->inputError('verificationCode', __('auth.two_factor.invalid_code'));

            return;
        }

        $this->twoFactorService->enableForUser($this->user, $tempSecret, $tempRecoveryCodes);

        session()->remove('2fa_temp_secret');
        session()->remove('2fa_setup_active');
        session()->set('2fa_show_recovery', true);

        $this->showSetup = false;
        $this->showRecoveryCodes = true;
        $this->verificationCode = null;

        $this->flashMessage(__('auth.two_factor.enabled_success'), 'success');
    }

    public function closeRecoveryCodes()
    {
        $this->clearSession();
        $this->showRecoveryCodes = false;
    }

    public function regenerateRecoveryCodes()
    {
        $this->twoFactorService = app(TwoFactorService::class);

        if (!$this->user->hasTwoFactorEnabled()) {
            return;
        }

        $newCodes = $this->twoFactorService->generateRecoveryCodes();
        $this->user->two_factor_recovery_codes = json_encode($this->twoFactorService->hashRecoveryCodes($newCodes));
        transaction($this->user)->run();

        session()->set('2fa_temp_codes', $newCodes);
        session()->set('2fa_show_recovery', true);

        $this->showRecoveryCodes = true;

        $this->flashMessage(__('auth.two_factor.recovery_codes'), 'success');
    }

    public function disable()
    {
        $this->twoFactorService = app(TwoFactorService::class);

        if (!$this->confirmed('disable_2fa_confirmation')) {
            $this->confirm(
                actionKey: 'disable_2fa_confirmation',
                message: __('auth.two_factor.confirm_disable'),
                type: 'warning',
                confirmText: __('auth.two_factor.disable'),
                cancelText: __('def.cancel')
            );

            return;
        }

        $this->twoFactorService->disableForUser($this->user);
        $this->clearSession();

        $this->flashMessage(__('auth.two_factor.disabled_success'), 'success');
    }

    protected function clearSession(): void
    {
        session()->remove('2fa_temp_secret');
        session()->remove('2fa_temp_codes');
        session()->remove('2fa_setup_active');
        session()->remove('2fa_show_recovery');
    }
}
