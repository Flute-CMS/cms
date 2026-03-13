<?php

namespace Flute\Admin\Packages\Redirects\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Redirect;
use Throwable;

class RedirectsScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.system';

    public $redirects;

    public function mount(): void
    {
        $this->name = __('admin-redirects.title');
        $this->description = __('admin-redirects.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-redirects.title'));

        $this->redirects = rep(Redirect::class)->select()
            ->load('conditionGroups')
            ->load('conditionGroups.conditions')
            ->orderBy('id', 'desc');
    }

    public function commandBar()
    {
        return [
            Button::make(__('admin-redirects.buttons.clear_cache'))
                ->icon('ph.bold.broom-bold')
                ->size('medium')
                ->method('clearRedirectsCache')
                ->type(Color::OUTLINE_WARNING),

            Button::make(__('admin-redirects.buttons.add'))
                ->icon('ph.bold.plus-bold')
                ->size('medium')
                ->redirect('/admin/redirects/edit')
                ->type(Color::PRIMARY),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('redirects', [
                TD::selection('id'),

                TD::make('fromUrl', __('admin-redirects.table.from'))
                    ->cantHide()
                    ->width('250px')
                    ->render(static fn (Redirect $r) => '<code>' . e($r->fromUrl) . '</code>'),

                TD::make('toUrl', __('admin-redirects.table.to'))
                    ->cantHide()
                    ->width('250px')
                    ->render(static fn (Redirect $r) => '<code>' . e($r->toUrl) . '</code>'),

                TD::make('conditionGroups', __('admin-redirects.table.conditions'))
                    ->width('120px')
                    ->alignCenter()
                    ->render(static function (Redirect $r) {
                        $count = 0;
                        foreach ($r->conditionGroups as $g) {
                            $count += count($g->conditions);
                        }

                        return $count > 0
                            ? '<span class="badge primary">' . $count . '</span>'
                            : '<span class="text-muted">—</span>';
                    }),

                TD::make('actions', __('admin-redirects.table.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(
                        static fn (Redirect $r) => DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list([
                                DropDownItem::make(__('admin-redirects.buttons.edit'))
                                    ->redirect('/admin/redirects/edit?id=' . $r->id)
                                    ->icon('ph.bold.pencil-bold')
                                    ->type(Color::OUTLINE_PRIMARY)
                                    ->size('small')
                                    ->fullWidth(),
                                DropDownItem::make(__('admin-redirects.buttons.delete'))
                                    ->confirm(__('admin-redirects.confirms.delete'))
                                    ->method('deleteRedirect', ['id' => $r->id])
                                    ->icon('ph.bold.trash-bold')
                                    ->type(Color::OUTLINE_DANGER)
                                    ->size('small')
                                    ->fullWidth(),
                            ])
                    ),
            ])
                ->empty('ph.regular.arrow-u-down-right', __('admin-redirects.empty.title'), __('admin-redirects.empty.sub'))
                ->emptyButton(
                    Button::make(__('admin-redirects.buttons.add'))
                        ->icon('ph.bold.plus-bold')
                        ->redirect('/admin/redirects/edit')
                )
                ->searchable(['fromUrl', 'toUrl'])
                ->bulkActions([
                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDeleteRedirects'),
                ]),
        ];
    }

    public function deleteRedirect()
    {
        $id = request()->input('id');

        $redirect = Redirect::query()
            ->load('conditionGroups')
            ->load('conditionGroups.conditions')
            ->where('id', $id)
            ->fetchOne();

        if (!$redirect) {
            $this->flashMessage(__('admin-redirects.messages.not_found'), 'error');

            return;
        }

        $this->deleteExistingConditions($redirect);
        $redirect->removeConditions();
        $redirect->delete();

        $this->flashMessage(__('admin-redirects.messages.delete_success'), 'success');
        $this->clearCache();
        $this->reloadRedirects();
    }

    public function bulkDeleteRedirects(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }

        foreach ($ids as $id) {
            $redirect = Redirect::query()
                ->load('conditionGroups')
                ->load('conditionGroups.conditions')
                ->where('id', $id)
                ->fetchOne();

            if (!$redirect) {
                continue;
            }

            $this->deleteExistingConditions($redirect);
            $redirect->removeConditions();
            $redirect->delete();
        }

        $this->flashMessage(__('admin-redirects.messages.delete_success'), 'success');
        $this->clearCache();
        $this->reloadRedirects();
    }

    public function clearRedirectsCache()
    {
        $this->clearCache();
        $this->flashMessage(__('admin-redirects.messages.cache_cleared'), 'success');
    }

    protected function deleteExistingConditions(Redirect $redirect): void
    {
        foreach ($redirect->conditionGroups as $group) {
            foreach ($group->conditions as $condition) {
                $condition->delete();
            }
            $group->delete();
        }
    }

    protected function clearCache(): void
    {
        try {
            cache()->delete('flute.redirects.all');
        } catch (Throwable $e) {
        }
    }

    protected function reloadRedirects(): void
    {
        $this->redirects = rep(Redirect::class)->select()
            ->load('conditionGroups')
            ->load('conditionGroups.conditions')
            ->orderBy('id', 'desc');
    }
}
