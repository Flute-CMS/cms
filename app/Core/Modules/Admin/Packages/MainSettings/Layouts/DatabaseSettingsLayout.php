<?php

namespace Flute\Admin\Packages\MainSettings\Layouts;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Support\Color;

class DatabaseSettingsLayout extends Table
{
    protected $target = 'databaseConnections';
    protected $perPage = 10;

    public function columns(): array
    {
        $this->searchable();

        return [
            TD::make('databaseName', __('admin-main-settings.databaseName'))
                ->width('200px')->render(function (Repository $item) {
                    return $item->get('databaseName') . '<small class="d-flex text-muted">' . $item->get('driver') . '</small>';
                })
                ->cantHide(),

            TD::make('host', __('admin-main-settings.host'))
                ->width('150px'),

            TD::make('user', __('admin-main-settings.user'))
                ->width('150px'),

            TD::make('database', __('admin-main-settings.database'))
                ->width('150px'),

            TD::make('prefix', __('admin-main-settings.prefix'))
                ->width('100px'),

            TD::make(__('admin-main-settings.actions'))
                ->align(TD::ALIGN_CENTER)
                ->disableSearch()
                ->width('100px')
                ->cantHide()
                ->render(function (Repository $item) {
                    return DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list([
                            DropDownItem::make(__('admin-main-settings.edit'))
                                ->modal('editDatabaseModal', [
                                    'databaseId' => $item->get('databaseName'),
                                ])
                                ->type(Color::OUTLINE_PRIMARY)
                                ->icon('ph.regular.pencil')
                                ->size('small')
                                ->fullWidth(),

                            DropDownItem::make(__('admin-main-settings.delete'))
                                ->fullWidth()
                                ->confirm(__('admin-main-settings.confirm_delete_database'))
                                ->method('removeDatabase', [
                                    'databaseId' => $item->get('databaseName'),
                                ])
                                ->type(Color::OUTLINE_DANGER)
                                ->icon('ph.regular.trash')
                                ->size('small'),
                        ]);
                }),
        ];
    }

    protected function commandBar(): array
    {
        return [
            Button::make(__('admin-main-settings.add_database'))
                ->icon('ph.regular.plus')
                ->size('small')
                ->type(Color::OUTLINE_PRIMARY)
                ->modal('addDatabaseModal'),
        ];
    }
}
