<?php

namespace Flute\Core\Modules\Profile\Controllers;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserBlock;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Throwable;

class ProfileAdminActionsController extends BaseController
{
    public function addBalance(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!user()->can($targetUser)) {
            return $this->error(__('def.no_permission'), 403);
        }

        $validation = $this->validate($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validation !== true) {
            return $validation;
        }

        $amount = (float) $request->input('amount');

        try {
            user()->topup($amount, $targetUser);

            $this->toast(__('profile.admin_actions.balance_added', ['amount' => $amount]), 'success');

            return $this->success(__('profile.admin_actions.balance_added', ['amount' => $amount]));
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function removeBalance(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!user()->can($targetUser)) {
            return $this->error(__('def.no_permission'), 403);
        }

        $validation = $this->validate($request->all(), [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        if ($validation !== true) {
            return $validation;
        }

        $amount = (float) $request->input('amount');

        if ($targetUser->balance < $amount) {
            $amount = $targetUser->balance;
        }

        try {
            if ($amount > 0) {
                user()->unbalance($amount, $targetUser);
            }

            $this->toast(__('profile.admin_actions.balance_removed', ['amount' => $amount]), 'success');

            return $this->success(__('profile.admin_actions.balance_removed', ['amount' => $amount]));
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function banUser(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!user()->can($targetUser)) {
            return $this->error(__('def.no_permission'), 403);
        }

        if (user()->getCurrentUser()?->id === $targetUser->id) {
            return $this->error(__('profile.admin_actions.cant_ban_self'), 403);
        }

        $validation = $this->validate($request->all(), [
            'reason' => ['required', 'string', 'max-str-len:500'],
            'blocked_until' => ['nullable', 'date'],
        ]);

        if ($validation !== true) {
            return $validation;
        }

        try {
            $block = new UserBlock();
            $block->user = $targetUser;
            $block->blockedBy = user()->getCurrentUser();
            $block->reason = $request->input('reason');
            $block->blockedFrom = new DateTimeImmutable();
            $block->blockedUntil = $request->input('blocked_until')
                ? new DateTimeImmutable($request->input('blocked_until'))
                : null;
            $block->save();

            $this->toast(__('profile.admin_actions.user_banned'), 'success');

            return $this->success(__('profile.admin_actions.user_banned'));
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function unbanUser(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!user()->can($targetUser)) {
            return $this->error(__('def.no_permission'), 403);
        }

        try {
            foreach ($targetUser->blocksReceived as $block) {
                $block->isActive = false;
                $block->save();
            }

            $this->toast(__('profile.admin_actions.user_unbanned'), 'success');

            return $this->success(__('profile.admin_actions.user_unbanned'));
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getAddBalanceModal(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        return view('flute::components.profile-admin-actions.add-balance-modal', [
            'user' => $targetUser,
        ]);
    }

    public function getRemoveBalanceModal(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        return view('flute::components.profile-admin-actions.remove-balance-modal', [
            'user' => $targetUser,
        ]);
    }

    public function getBanModal(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        return view('flute::components.profile-admin-actions.ban-modal', [
            'user' => $targetUser,
        ]);
    }

    public function clearSessions(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!user()->can($targetUser)) {
            return $this->error(__('def.no_permission'), 403);
        }

        if (user()->getCurrentUser()?->id === $targetUser->id) {
            return $this->error(__('profile.admin_actions.cant_clear_own_sessions'), 403);
        }

        try {
            foreach ($targetUser->rememberTokens as $token) {
                $token->delete();
            }

            foreach ($targetUser->userDevices as $device) {
                $device->delete();
            }

            $this->toast(__('profile.admin_actions.sessions_cleared'), 'success');

            return $this->success(__('profile.admin_actions.sessions_cleared'));
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function toggleVerified(FluteRequest $request, int $id)
    {
        if (!user()->can('admin.users')) {
            return $this->error(__('def.no_permission'), 403);
        }

        $targetUser = User::findByPK($id);

        if (!$targetUser) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!user()->can($targetUser)) {
            return $this->error(__('def.no_permission'), 403);
        }

        try {
            $targetUser->verified = !$targetUser->verified;
            $targetUser->save();

            $message = $targetUser->verified
                ? __('profile.admin_actions.user_verified')
                : __('profile.admin_actions.user_unverified');

            $this->toast($message, 'success');

            return $this->success($message);
        } catch (Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
