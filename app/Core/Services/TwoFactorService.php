<?php

namespace Flute\Core\Services;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TwoFactorService
{
    protected ConfigurationService $config;

    public function __construct(ConfigurationService $config)
    {
        $this->config = $config;
    }

    /**
     * Check if 2FA is enabled globally.
     */
    public function isEnabled(): bool
    {
        if (!class_exists(TOTP::class) || !class_exists(Base32::class)) {
            return false;
        }

        return (bool) $this->config->get('auth.two_factor.enabled', false);
    }

    /**
     * Check if 2FA is forced for all users.
     */
    public function isForced(): bool
    {
        if (!class_exists(TOTP::class) || !class_exists(Base32::class)) {
            return false;
        }

        return (bool) $this->config->get('auth.two_factor.force', false);
    }

    /**
     * Get the issuer name for TOTP.
     */
    public function getIssuer(): string
    {
        $issuer = $this->config->get('auth.two_factor.issuer', '');

        return !empty($issuer) ? $issuer : $this->config->get('app.name', 'Flute');
    }

    /**
     * Get the time window for TOTP verification.
     */
    public function getWindow(): int
    {
        return (int) $this->config->get('auth.two_factor.window', 1);
    }

    /**
     * Generate a new secret key for 2FA.
     */
    public function generateSecretKey(): string
    {
        return Base32::encodeUpper(random_bytes(20));
    }

    /**
     * Create a TOTP instance for a user.
     */
    public function createTOTP(User $user, ?string $secret = null): TOTP
    {
        $secret ??= $user->two_factor_secret;

        $totp = TOTP::create($secret);
        $totp->setLabel($user->login ?? $user->email ?? (string) $user->id);
        $totp->setIssuer($this->getIssuer());

        return $totp;
    }

    /**
     * Get the QR code provisioning URI for a user.
     */
    public function getQrCodeUri(User $user, string $secret): string
    {
        $totp = $this->createTOTP($user, $secret);

        return $totp->getProvisioningUri();
    }

    /**
     * Verify a TOTP code for a user.
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        $totp = $this->createTOTP($user);

        return $totp->verify($code, null, $this->getWindow());
    }

    /**
     * Verify a TOTP code using a specific secret (for setup).
     */
    public function verifyCodeWithSecret(string $secret, string $code): bool
    {
        $totp = TOTP::create($secret);

        return $totp->verify($code, null, $this->getWindow());
    }

    /**
     * Generate recovery codes for a user.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    /**
     * Hash recovery codes for storage.
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(static fn ($code) => hash('sha256', $code), $codes);
    }

    /**
     * Verify a recovery code.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_recovery_codes)) {
            return false;
        }

        $codes = json_decode($user->two_factor_recovery_codes, true);

        if (!is_array($codes)) {
            return false;
        }

        $hashedCode = hash('sha256', $code);

        $index = array_search($hashedCode, $codes, true);

        if ($index !== false) {
            // Remove used code
            unset($codes[$index]);
            $user->two_factor_recovery_codes = json_encode(array_values($codes));
            transaction($user)->run();

            return true;
        }

        return false;
    }

    /**
     * Enable 2FA for a user.
     */
    public function enableForUser(User $user, string $secret, array $recoveryCodes): void
    {
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = json_encode($this->hashRecoveryCodes($recoveryCodes));
        $user->two_factor_confirmed_at = new DateTimeImmutable();

        transaction($user)->run();
    }

    /**
     * Disable 2FA for a user.
     */
    public function disableForUser(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;

        transaction($user)->run();
    }

    /**
     * Check if a user has 2FA enabled.
     */
    public function isEnabledForUser(User $user): bool
    {
        return !empty($user->two_factor_secret) && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Check if user needs to complete 2FA verification.
     */
    public function needsVerification(User $user): bool
    {
        return $this->isEnabledForUser($user);
    }

    /**
     * Check if user needs to set up 2FA (when forced).
     */
    public function needsSetup(User $user): bool
    {
        return $this->isEnabled() && $this->isForced() && !$this->isEnabledForUser($user);
    }
}
