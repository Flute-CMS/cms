<?php

namespace Flute\Admin\Packages\User\Screens\Concerns;

use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Throwable;

trait HandlesSocialNetworks
{
    public function addSocialNetworkModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('socialNetwork')
                    ->preload()
                    ->fromDatabase('socials', 'key', 'id', ['key', 'id'])
                    ->placeholder(__('admin-users.fields.social_network.placeholder')),
            )
                ->label(__('admin-users.fields.social_network.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('value')->type('text')->placeholder(__('admin-users.fields.social_value.placeholder')),
            )
                ->label(__('admin-users.fields.social_value.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('url')->type('url')->placeholder(__('admin-users.fields.social_url.placeholder')),
            )->label(__('admin-users.fields.social_url.label')),

            LayoutFactory::field(
                Input::make('name')->type('text')->placeholder(__('admin-users.fields.social_name.placeholder')),
            )->label(__('admin-users.fields.social_name.label')),
        ])
            ->title(__('admin-users.title.add_social_network'))
            ->applyButton(__('admin-users.buttons.add_social'))
            ->method('addSocialNetwork');
    }

    public function addSocialNetwork()
    {
        $data = request()->input();

        $validation = $this->validate([
            'socialNetwork' => ['required', 'integer', 'exists:social_networks,id'],
            'value' => ['required', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'url', 'max-str-len:255'],
            'name' => ['nullable', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            $this->usersService->addSocialNetwork($this->user, $data);
            orm()->getHeap()->clean();
            $this->flashMessage(__('admin-users.messages.social_added'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function editSocialNetworkModal(Repository $parameters)
    {
        $networkId = $parameters->get('networkId');
        $network = rep(UserSocialNetwork::class)->findByPK($networkId);

        if (!$network) {
            $this->flashMessage(__('admin-users.messages.social_not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('value')
                    ->type('text')
                    ->value($network->value)
                    ->placeholder(__('admin-users.fields.social_value.placeholder')),
            )
                ->label(__('admin-users.fields.social_value.label'))
                ->required(),

            LayoutFactory::field(
                Input::make('url')
                    ->type('url')
                    ->value($network->url)
                    ->placeholder(__('admin-users.fields.social_url.placeholder')),
            )->label(__('admin-users.fields.social_url.label')),

            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->value($network->name)
                    ->placeholder(__('admin-users.fields.social_name.placeholder')),
            )->label(__('admin-users.fields.social_name.label')),
        ])
            ->title(__('admin-users.title.edit_social_network'))
            ->applyButton(__('admin-users.buttons.save_social'))
            ->method('updateSocialNetwork');
    }

    public function updateSocialNetwork()
    {
        $data = request()->input();
        $networkId = $this->modalParams->get('networkId');

        $validation = $this->validate([
            'value' => ['required', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'url', 'max-str-len:255'],
            'name' => ['nullable', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            $this->usersService->updateSocialNetwork($networkId, $data);
            orm()->getHeap()->clean();
            $this->flashMessage(__('admin-users.messages.social_updated'), 'success');
            $this->closeModal();
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function toggleSocialNetworkVisibility()
    {
        $networkId = request()->input('networkId');

        try {
            $this->usersService->toggleSocialNetworkVisibility($networkId);
            orm()->getHeap()->clean();
            $this->flashMessage(__('admin-users.messages.social_visibility_changed'), 'success');
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function deleteSocialNetwork()
    {
        $networkId = request()->input('networkId');

        try {
            $this->usersService->deleteSocialNetwork($networkId);
            orm()->getHeap()->clean();
            $this->flashMessage(__('admin-users.messages.social_deleted'), 'success');
            $this->initUser();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
