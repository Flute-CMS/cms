<?php

namespace Flute\Admin\Packages\Social\Screens;

use Carbon\CarbonInterval;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Support\Color;

class SocialScreen extends Screen
{
    public ?string $name = 'admin-social.title.social';
    public ?string $description = 'admin-social.title.description';
    public ?string $permission = 'admin.socials';

    public $socials;

    public function mount() : void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-social.title.social'));

        $this->socials = rep(SocialNetwork::class)->select()->orderBy('id', 'desc');
    }


    public function layout() : array
    {
        return [
            LayoutFactory::table('socials', [
                TD::make()->title(__('admin-social.table.social'))->render(fn(SocialNetwork $social) => view('admin-social::cells.main', compact('social')))->minWidth('200px')->cantHide(),

                TD::make('cooldown_time', __('admin-social.table.cooldown'))
                    ->popover(__('admin-social.fields.cooldown_time.popover'))
                    ->render(fn(SocialNetwork $social) => $social->cooldownTime ? CarbonInterval::seconds($social->cooldownTime)->cascade()->forHumans() : 'Не ограничено'),

                TD::make('allow_to_register', __('admin-social.table.registration'))
                    ->popover(__('admin-social.fields.allow_register.help'))
                    ->render(fn(SocialNetwork $social) => view('admin-social::cells.allow_to_register', compact('social'))),

                TD::make('enabled', __('admin-social.table.status'))->render(fn(SocialNetwork $social) => view('admin-social::cells.enabled', compact('social'))),

                TD::make('actions', __('admin-social.table.actions'))->width('200px')->alignCenter()->render(
                    fn(SocialNetwork $social) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make($social->enabled ? __('admin-social.buttons.disable') : __('admin-social.buttons.enable'))
                                ->method('toggle', ['id' => $social->id])
                                ->icon($social->enabled ? 'ph.bold.power-bold' : 'ph.bold.check-circle-bold')
                                ->type($social->enabled ? Color::OUTLINE_WARNING : Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth(),

                            DropDownItem::make(__('admin-social.buttons.edit'))->redirect(url('/admin/socials/' . $social->id . '/edit'))->icon('ph.bold.pencil-bold')->type(Color::OUTLINE_PRIMARY)->size('small')->fullWidth(),
                            DropDownItem::make(__('admin-social.buttons.delete'))->confirm(__('admin-social.confirms.delete'))->method('delete', ['delete-id' => $social->id])->icon('ph.bold.trash-bold')->type(Color::OUTLINE_DANGER)->size('small')->fullWidth(),
                        ])
                ),
            ])
                ->searchable(['key', 'id'])
                ->commands([
                    Button::make(__('admin-social.buttons.add'))->redirect(url('/admin/socials/add'))->icon('ph.bold.plus-bold'),
                ])
        ];
    }

    public function toggle() : void
    {
        if (
            $this->validate([
                'id' => ['required', 'string'],
            ], request()->input())
        ) {
            $id = request()->input('id');

            $social = rep(SocialNetwork::class)->findByPK($id);

            if (!$social) {
                $this->flashMessage(__('admin-social.messages.toggle_error'), 'error');
                return;
            }

            $social->enabled = !$social->enabled;
            transaction($social)->run();

            $this->socials = rep(SocialNetwork::class)->select();
            $this->flashMessage(__('admin-social.messages.toggle_success'));
        } else {
            $this->flashMessage(__('admin-social.messages.toggle_error'), 'error');
        }
    }

    public function delete() : void
    {
        if (
            $this->validate([
                'delete-id' => ['required', 'string'],
            ], request()->input())
        ) {
            $id = request()->input('delete-id');

            $social = rep(SocialNetwork::class)->findByPK($id);

            if ($social) {
                transaction($social, 'delete')->run();
                $this->socials = rep(SocialNetwork::class)->select();
                $this->flashMessage(__('admin-social.messages.delete_success'));
            } else {
                $this->flashMessage(__('admin-social.messages.delete_error'), 'error');
            }
        } else {
            $this->flashMessage(__('admin-social.messages.delete_error'), 'error');
        }
    }
}
