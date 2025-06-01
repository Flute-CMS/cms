<?php

namespace Flute\Admin\Packages\MainSettings\Layouts;

use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Layouts\Rows;

class EditDatabaseLayout extends Rows
{
    public function fields(): array
    {
        return [
            LayoutFactory::field(
                Select::make('driver')
                    ->options([
                        'mysql' => 'MySQL',
                        'postgres' => 'PostgreSQL',
                    ])
                    ->value(request()->input('driver', $this->query->get('driver')))
                    ->placeholder(__('admin-main-settings.placeholders.db_driver'))
                    ->required()
            )->label(__('admin-main-settings.labels.db_driver'))->required(),

            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->value(request()->input('databaseName', $this->query->get('databaseId')))
                    ->placeholder(__('admin-main-settings.placeholders.database_name'))
                    ->readonly()
                    ->required()
            )->label(__('admin-main-settings.labels.database_name'))->required(),

            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value(request()->input('host', $this->query->get('host')))
                    ->placeholder(__('admin-main-settings.placeholders.db_host'))
                    ->required()
            )->label(__('admin-main-settings.labels.host'))->required(),

            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value(request()->input('port', $this->query->get('port', 3306)))
                    ->placeholder(__('admin-main-settings.placeholders.db_port'))
                    ->required()
            )->label(__('admin-main-settings.labels.port'))->required(),

            LayoutFactory::field(
                Input::make('user')
                    ->type('text')
                    ->value(request()->input('user', $this->query->get('user')))
                    ->placeholder(__('admin-main-settings.placeholders.db_user'))
                    ->required()
            )->label(__('admin-main-settings.labels.user'))->required(),

            LayoutFactory::field(
                Input::make('database')
                    ->type('text')
                    ->value(request()->input('database', $this->query->get('database')))
                    ->placeholder(__('admin-main-settings.placeholders.db_database'))
                    ->required()
            )->label(__('admin-main-settings.labels.database'))->required(),

            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->value(request()->input('password', $this->query->get('password')))
                    ->placeholder(__('admin-main-settings.placeholders.db_password'))
                    ->required()
            )->label(__('admin-main-settings.labels.password')),

            LayoutFactory::field(
                Input::make('prefix')
                    ->type('text')
                    ->value(request()->input('prefix', $this->query->get('prefix')))
                    ->placeholder(__('admin-main-settings.placeholders.db_prefix'))
            )->label(__('admin-main-settings.labels.prefix'))
                ->popover(__('admin-main-settings.popovers.prefix'))
                ->small(__('admin-main-settings.examples.db_prefix')),
        ];
    }
}
