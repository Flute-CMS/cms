<?php

namespace Flute\Admin\Packages\Footer\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\FooterItem;
use Flute\Core\Database\Entities\FooterSocial;

class FooterListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.footer';

    public $footerItems;
    public $socials;

    public function mount(): void
    {
        // $this->name = __('admin-footer.title');
        // $this->description = __('admin-footer.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-footer.title'));

        $this->loadFooterItems();
        $this->loadSocials();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::tabs([
                Tab::make(__('admin-footer.tabs.main_elements'))
                    ->badge(sizeof($this->footerItems))
                    ->layouts([
                        LayoutFactory::sortable('footerItems', [
                            Sight::make('title', __('admin-footer.table.title'))->render(fn (FooterItem $footerItem) => view('admin-footer::cells.item-title', compact('footerItem'))),
                            Sight::make('actions', __('admin-footer.table.actions'))
                                ->render(
                                    fn (FooterItem $footerItem) => DropDown::make()
                                        ->icon('ph.regular.dots-three-outline-vertical')
                                        ->list([
                                            DropDownItem::make(__('def.edit'))
                                                ->modal('editFooterItemModal', ['footerItem' => $footerItem->id])
                                                ->icon('ph.bold.pencil-bold')
                                                ->type(Color::OUTLINE_PRIMARY)
                                                ->size('small')
                                                ->fullWidth(),
                                            DropDownItem::make(__('def.delete'))
                                                ->confirm(__('admin-footer.confirms.delete_item'))
                                                ->method('deleteFooterItem', ['id' => $footerItem->id])
                                                ->icon('ph.bold.trash-bold')
                                                ->type(Color::OUTLINE_DANGER)
                                                ->size('small')
                                                ->fullWidth(),
                                        ])
                                ),
                        ])
                            ->onSortEnd('updateFooterItemPositions')
                            ->commands([
                                Button::make(__('def.create'))
                                    ->icon('ph.bold.plus-bold')
                                    ->size('medium')
                                    ->modal('createFooterItemModal')
                                    ->type(Color::PRIMARY),
                            ])
                            ->title(__('admin-footer.sections.main_links.title'))
                            ->description(__('admin-footer.sections.main_links.description')),
                    ]),
                Tab::make(__('admin-footer.tabs.social'))
                    ->badge(sizeof($this->socials))
                    ->layouts([
                        LayoutFactory::table('socials', [
                            TD::make('icon', __('admin-footer.table.icon'))->render(fn (FooterSocial $footerSocial) => view('admin-footer::cells.social-icon', compact('footerSocial'))),
                            TD::make('url', __('admin-footer.table.url'))->render(fn (FooterSocial $footerSocial) => '<a class="hover-accent" href="' . $footerSocial->url . '" target="_blank">' . $footerSocial->url . '</a>'),
                            TD::make('actions', __('admin-footer.table.actions'))
                                ->width('150px')
                                ->alignCenter()
                                ->render(fn (FooterSocial $footerSocial) => DropDown::make()
                                    ->icon('ph.regular.dots-three-outline-vertical')
                                    ->list([
                                        DropDownItem::make(__('admin-footer.buttons.edit'))
                                            ->modal('editFooterSocialModal', ['footerSocial' => $footerSocial->id])
                                            ->icon('ph.bold.pencil-bold')
                                            ->type(Color::OUTLINE_PRIMARY)
                                            ->size('small')
                                            ->fullWidth(),
                                        DropDownItem::make(__('admin-footer.buttons.delete'))
                                            ->confirm(__('admin-footer.confirms.delete_social'))
                                            ->method('deleteFooterSocial', ['id' => $footerSocial->id])
                                            ->icon('ph.bold.trash-bold')
                                            ->type(Color::OUTLINE_DANGER)
                                            ->size('small')
                                            ->fullWidth(),
                                    ])),
                        ])
                            ->searchable()
                            ->title(__('admin-footer.sections.social_links.title'))
                            ->description(__('admin-footer.sections.social_links.description'))
                            ->commands([
                                Button::make(__('def.create'))
                                    ->icon('ph.bold.plus-bold')
                                    ->size('medium')
                                    ->modal('createFooterSocialModal')
                                    ->type(Color::PRIMARY),
                            ]),
                    ]),
            ])->slug('footer'),
        ];
    }

    protected function loadFooterItems()
    {
        $this->footerItems = rep(FooterItem::class)
            ->select()
            ->orderBy('position', 'asc')
            ->where('parent_id', null)
            ->load('children', [
                'load' => function ($qb) {
                    $qb->orderBy('position', 'asc');
                },
            ])
            ->fetchAll();
    }

    protected function loadSocials()
    {
        $this->socials = rep(FooterSocial::class)->select()->orderBy('id', 'desc')->fetchAll();
    }

    /**
     * Обновление позиций элементов футера после сортировки
     */
    public function updateFooterItemPositions()
    {
        $sortableResult = json_decode(request()->input('sortableResult'), true);
        if (!$sortableResult) {
            $this->flashMessage(__('admin-footer.messages.invalid_sort_data'), 'danger');

            return;
        }

        $this->reorderItems($sortableResult);

        orm()->getHeap()->clean();

        $this->clearFooterCache();

        $this->loadFooterItems();
    }

    /**
     * Recalculate positions recursively without sharing the counter between sibling groups.
     * Ensures stable ordering after drag-and-drop.
     */
    private function reorderItems(array $items, ?FooterItem $parent = null): void
    {
        $position = 0;

        foreach ($items as $item) {
            $footerItem = FooterItem::findByPK($item['id']);
            if (!$footerItem) {
                continue;
            }

            $footerItem->position = ++$position;
            $footerItem->parent = $parent;
            $footerItem->save();

            if (!empty($item['children'])) {
                $this->reorderItems($item['children'], $footerItem);
            }
        }
    }

    /**
     * Модальное окно для добавления нового элемента футера
     */
    public function createFooterItemModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.footer_item.fields.title.placeholder'))
            )
                ->label(__('admin-footer.modal.footer_item.fields.title.label'))
                ->required()
                ->small(__('admin-footer.modal.footer_item.fields.title.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-footer.modal.footer_item.fields.icon.placeholder'))
            )
                ->label(__('admin-footer.modal.footer_item.fields.icon.label'))
                ->small(__('admin-footer.modal.footer_item.fields.icon.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.footer_item.fields.url.placeholder'))
            )
                ->label(__('admin-footer.modal.footer_item.fields.url.label'))
                ->small(__('admin-footer.modal.footer_item.fields.url.help')),

            LayoutFactory::field(
                CheckBox::make('new_tab')
                    ->label(__('admin-footer.modal.footer_item.fields.new_tab.label'))
                    ->popover(__('admin-footer.modal.footer_item.fields.new_tab.help')),
            ),
        ])
            ->title(__('admin-footer.modal.footer_item.create_title'))
            ->applyButton(__('def.create'))
            ->method('saveFooterItem');
    }

    /**
     * Сохранение нового элемента футера
     */
    public function saveFooterItem()
    {
        $data = request()->input();

        $validation = $this->validate([
            'title' => ['required', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        $lastItem = FooterItem::query()
            ->where('parent_id', $data['parent_id'] ?? null)
            ->orderBy('position', 'desc')
            ->fetchOne();
        $position = $lastItem ? $lastItem->position + 1 : 1;

        $footerItem = new FooterItem();
        $footerItem->title = $data['title'];
        $footerItem->icon = $data['icon'] ?? null;
        $footerItem->url = $data['url'] ?? null;
        $footerItem->new_tab = isset($data['new_tab']) && $data['new_tab'] ? true : false;
        $footerItem->position = $position;

        $footerItem->save();

        $this->flashMessage(__('admin-footer.messages.item_created'), 'success');
        $this->closeModal();

        // Clear footer cache to ensure persistence after save
        $this->clearFooterCache();

        $this->loadFooterItems();
    }

    /**
     * Модальное окно для редактирования элемента футера
     */
    public function editFooterItemModal(Repository $parameters)
    {
        $footerItemId = $parameters->get('footerItem');
        $footerItem = FooterItem::findByPK($footerItemId);
        if (!$footerItem) {
            $this->flashMessage(__('admin-footer.messages.item_not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.footer_item.fields.title.placeholder'))
                    ->value($footerItem->title)
            )
                ->label(__('admin-footer.modal.footer_item.fields.title.label'))
                ->required()
                ->small(__('admin-footer.modal.footer_item.fields.title.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-footer.modal.footer_item.fields.icon.placeholder'))
                    ->value($footerItem->icon)
            )
                ->label(__('admin-footer.modal.footer_item.fields.icon.label'))
                ->small(__('admin-footer.modal.footer_item.fields.icon.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.footer_item.fields.url.placeholder'))
                    ->value($footerItem->url)
            )
                ->label(__('admin-footer.modal.footer_item.fields.url.label'))
                ->small(__('admin-footer.modal.footer_item.fields.url.help')),

            LayoutFactory::field(
                CheckBox::make('new_tab')
                    ->label(__('admin-footer.modal.footer_item.fields.new_tab.label'))
                    ->popover(__('admin-footer.modal.footer_item.fields.new_tab.help'))
                    ->value($footerItem->new_tab)
            ),
        ])
            ->title(__('admin-footer.modal.footer_item.edit_title'))
            ->applyButton(__('def.save'))
            ->method('updateFooterItem');
    }

    /**
     * Обновление существующего элемента футера
     */
    public function updateFooterItem()
    {
        $data = request()->input();
        $footerItemId = $this->modalParams->get('footerItem');

        $footerItem = FooterItem::findByPK($footerItemId);
        if (!$footerItem) {
            $this->flashMessage(__('admin-footer.messages.item_not_found'), 'error');

            return;
        }

        $validation = $this->validate([
            'title' => ['required', 'string', 'max-str-len:255'],
            'icon' => ['nullable', 'string', 'max-str-len:255'],
            'url' => ['nullable', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        $footerItem->title = $data['title'];
        $footerItem->icon = $data['icon'] ?? null;
        $footerItem->url = $data['url'] ?? null;
        $footerItem->new_tab = isset($data['new_tab']) && $data['new_tab'] ? true : false;

        $footerItem->save();

        $this->flashMessage(__('admin-footer.messages.item_updated'), 'success');
        $this->closeModal();

        $this->clearFooterCache();

        $this->loadFooterItems();
    }

    /**
     * Удаление элемента футера
     */
    public function deleteFooterItem()
    {
        $id = request()->input('id');

        $footerItem = FooterItem::findByPK($id);
        if (!$footerItem) {
            $this->flashMessage(__('admin-footer.messages.item_not_found'), 'error');

            return;
        }

        if (!empty($footerItem->children)) {
            $this->flashMessage('Невозможно удалить элемент футера, так как у него есть дочерние элементы.', 'warning');

            return;
        }

        $footerItem->delete();
        $this->flashMessage(__('admin-footer.messages.item_deleted'), 'success');

        $this->clearFooterCache();

        $this->loadFooterItems();
    }

    /**
     * Модальное окно для добавления новой соц.сети
     */
    public function createFooterSocialModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('social_name')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.social.fields.name.placeholder'))
            )
                ->label(__('admin-footer.modal.social.fields.name.label'))
                ->required()
                ->small(__('admin-footer.modal.social.fields.name.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-footer.modal.social.fields.icon.placeholder'))
            )
                ->label(__('admin-footer.modal.social.fields.icon.label'))
                ->required()
                ->small(__('admin-footer.modal.social.fields.icon.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.social.fields.url.placeholder'))
            )
                ->label(__('admin-footer.modal.social.fields.url.label'))
                ->required()
                ->small(__('admin-footer.modal.social.fields.url.help')),
        ])
            ->title(__('admin-footer.modal.social.create_title'))
            ->applyButton(__('def.create'))
            ->method('saveFooterSocial');
    }

    /**
     * Сохранение новой соц.сети
     */
    public function saveFooterSocial()
    {
        $data = request()->input();

        $validation = $this->validate([
            'social_name' => ['required', 'string', 'max-str-len:255'],
            'icon' => ['required', 'string', 'max-str-len:255'],
            'url' => ['required', 'string', 'max-str-len:255', 'url'],
        ], $data);

        if (!$validation) {
            return;
        }

        $footerSocial = new FooterSocial();
        $footerSocial->name = $data['social_name'];
        $footerSocial->icon = $data['icon'];
        $footerSocial->url = $data['url'];

        $footerSocial->save();

        $this->flashMessage(__('admin-footer.messages.social_created'), 'success');
        $this->closeModal();

        $this->clearFooterCache();

        $this->loadSocials();
    }

    /**
     * Модальное окно для редактирования соц.сети
     */
    public function editFooterSocialModal(Repository $parameters)
    {
        $footerSocialId = $parameters->get('footerSocial');
        $footerSocial = FooterSocial::findByPK($footerSocialId);
        if (!$footerSocial) {
            $this->flashMessage(__('admin-footer.messages.social_not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('social_name')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.social.fields.name.placeholder'))
                    ->value($footerSocial->name)
            )
                ->label(__('admin-footer.modal.social.fields.name.label'))
                ->required()
                ->small(__('admin-footer.modal.social.fields.name.help')),

            LayoutFactory::field(
                Input::make('icon')
                    ->type('icon')
                    ->placeholder(__('admin-footer.modal.social.fields.icon.placeholder'))
                    ->value($footerSocial->icon)
            )
                ->label(__('admin-footer.modal.social.fields.icon.label'))
                ->required()
                ->small(__('admin-footer.modal.social.fields.icon.help')),

            LayoutFactory::field(
                Input::make('url')
                    ->type('text')
                    ->placeholder(__('admin-footer.modal.social.fields.url.placeholder'))
                    ->value($footerSocial->url)
            )
                ->label(__('admin-footer.modal.social.fields.url.label'))
                ->required()
                ->small(__('admin-footer.modal.social.fields.url.help')),
        ])
            ->title(__('admin-footer.modal.social.edit_title'))
            ->applyButton(__('def.save'))
            ->method('updateFooterSocial');
    }

    /**
     * Обновление существующей соц.сети
     */
    public function updateFooterSocial()
    {
        $data = request()->input();
        $footerSocialId = $this->modalParams->get('footerSocial');

        $footerSocial = FooterSocial::findByPK($footerSocialId);
        if (!$footerSocial) {
            $this->flashMessage(__('admin-footer.messages.social_not_found'), 'error');

            return;
        }

        $validation = $this->validate([
            'social_name' => ['required', 'string', 'max-str-len:255'],
            'icon' => ['required', 'string', 'max-str-len:255'],
            'url' => ['required', 'string', 'max-str-len:255', 'url'],
        ], $data);

        if (!$validation) {
            return;
        }

        $footerSocial->name = $data['social_name'];
        $footerSocial->icon = $data['icon'];
        $footerSocial->url = $data['url'];

        $footerSocial->save();

        $this->flashMessage(__('admin-footer.messages.social_updated'), 'success');
        $this->closeModal();

        $this->clearFooterCache();

        $this->loadSocials();
    }

    /**
     * Удаление соц.сети
     */
    public function deleteFooterSocial()
    {
        $id = request()->input('id');

        $footerSocial = FooterSocial::findByPK($id);
        if (!$footerSocial) {
            $this->flashMessage(__('admin-footer.messages.social_not_found'), 'error');

            return;
        }

        $footerSocial->delete();
        $this->flashMessage(__('admin-footer.messages.social_deleted'), 'success');

        $this->clearFooterCache();

        $this->loadSocials();
    }

    /**
     * Clear all cached footer keys matching the cache key prefix.
     */
    private function clearFooterCache(): void
    {
        try {
            $pattern = \Flute\Core\Services\FooterService::CACHE_KEY . '*';
            $keys = cache()->getKeys($pattern);
            foreach ($keys as $key) {
                cache()->delete($key);
            }
        } catch (\Throwable $e) {
            // Swallow exceptions to avoid breaking admin UI
        }
    }
}
