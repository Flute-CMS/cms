<?php

namespace Flute\Admin\Packages\Pages\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Components\Cells\DateTime;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Page;

class PageListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.pages';

    public $pages;

    public function mount(): void
    {
        $this->name = __('admin-pages.title.list');
        $this->description = __('admin-pages.title.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-pages.title.list'));

        $this->pages = rep(Page::class)->select()->load('blocks');
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('pages', [
                TD::make('title')
                    ->title(__('admin-pages.fields.title.label'))
                    ->render(fn (Page $page) => view('admin-pages::cells.page-info', compact('page')))
                    ->minWidth('250px')
                    ->cantHide(),

                TD::make('route')
                    ->title(__('admin-pages.fields.route.label'))
                    ->render(fn (Page $page) => '<code>' . $page->route . '</code>')
                    ->width('200px')
                    ->sort()
                    ->cantHide(),

                TD::make('blocks_count')
                    ->title(__('admin-pages.title.blocks'))
                    ->render(fn (Page $page) => count($page->blocks))
                    ->width('200px')
                    ->alignCenter(),

                TD::make('createdAt')
                    ->title(__('admin-pages.fields.created_at'))
                    ->asComponent(DateTime::class)
                    ->width('200px')
                    ->sort()
                    ->cantHide(),

                TD::make('actions')
                    ->title(__('admin-pages.buttons.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(
                        fn (Page $page) => DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list([
                                DropDownItem::make(__('admin-pages.buttons.edit'))
                                    ->redirect(url('/admin/pages/' . $page->id . '/edit'))
                                    ->icon('ph.bold.pencil-bold')
                                    ->type(Color::OUTLINE_PRIMARY)
                                    ->size('small')
                                    ->fullWidth(),

                                DropDownItem::make(__('admin-pages.buttons.delete'))
                                    ->confirm(__('admin-pages.confirms.delete_page'))
                                    ->method('delete', ['delete-id' => $page->id])
                                    ->icon('ph.bold.trash-bold')
                                    ->type(Color::OUTLINE_DANGER)
                                    ->size('small')
                                    ->fullWidth(),

                                DropDownItem::make(__('admin-pages.buttons.goto'))
                                    ->href(url($page->route))
                                    ->icon('ph.bold.arrow-right-bold')
                                    ->type(Color::OUTLINE_PRIMARY)
                                    ->size('small')
                                    ->fullWidth(),
                            ])
                    ),
            ])
                ->searchable(['title', 'route', 'description'])
                ->commands([
                    Button::make(__('admin-pages.buttons.add'))
                        ->icon('ph.bold.plus-bold')
                        ->redirect(url('/admin/pages/add')),
                ]),
        ];
    }

    public function delete(): void
    {
        $page = Page::findByPK(request()->get('delete-id'));

        if ($page) {
            foreach ($page->blocks as $block) {
                $block->delete();
            }

            $page->delete();
        }

        $this->flashMessage(__('admin-pages.messages.page_deleted'));
    }
}
