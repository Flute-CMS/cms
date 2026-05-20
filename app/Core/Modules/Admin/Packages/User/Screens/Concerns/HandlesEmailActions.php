<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use Throwable;

trait HandlesEmailActions
{
    public function verifyUserEmail()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        $this->user->verified = true;
        $this->user->save();
        $this->flashMessage(__('admin-users.messages.email_verified'), 'success');
        $this->initUser();
    }

    public function sendVerificationEmail()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        if (!$this->user->email) {
            $this->flashMessage(__('admin-users.messages.no_email'), 'error');

            return;
        }

        try {
            template()->addNamespace('flute', path('app/Themes/standard/views'));
            $verificationToken = auth()->createVerificationToken($this->user)->rawToken;
            $html = template()->render('flute::emails.confirmation', [
                'url' => url('confirm/' . $verificationToken),
                'name' => $this->user->name,
            ]);
            email()->send($this->user->email, __('auth.confirmation.subject'), $html);
            $this->flashMessage(__('admin-users.messages.verification_sent'), 'success');
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function applyPendingEmail()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        if (empty($this->user->pendingEmail)) {
            return;
        }

        $this->user->email = $this->user->pendingEmail;
        $this->user->pendingEmail = null;
        $this->user->verified = true;
        $this->user->save();
        $this->flashMessage(__('admin-users.messages.pending_email_applied'), 'success');
        $this->initUser();
    }

    public function cancelPendingEmail()
    {
        if (!$this->canEditDisplayedUser()) {
            $this->flashMessage(__('admin-users.messages.no_permission'), 'error');

            return;
        }

        $this->user->pendingEmail = null;
        $this->user->save();
        $this->flashMessage(__('admin-users.messages.pending_email_cancelled'), 'success');
        $this->initUser();
    }

    protected function hasEmailActions(): bool
    {
        if (!$this->canEditDisplayedUser()) {
            return false;
        }

        return (
            !empty($this->user->pendingEmail)
            || !$this->user->verified && !empty($this->user->email)
            || config('auth.registration.confirm_email')
        );
    }
}
