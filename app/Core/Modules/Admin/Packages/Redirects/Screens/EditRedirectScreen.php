<?php

namespace Flute\Admin\Packages\Redirects\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\ConditionGroup;
use Flute\Core\Database\Entities\Redirect;
use Flute\Core\Database\Entities\RedirectCondition;
use Throwable;

class EditRedirectScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.system';

    public ?Redirect $redirectEntity = null;

    public bool $isEditMode = false;

    protected $id = null;

    public function mount(): void
    {
        $this->loadJS('app/Core/Modules/Admin/Packages/Redirects/Resources/assets/js/conditions-editor.js');

        $this->id = (int) request()->input('id');

        if ($this->id) {
            $this->redirectEntity = Redirect::query()
                ->load('conditionGroups')
                ->load('conditionGroups.conditions')
                ->where('id', $this->id)
                ->fetchOne();

            if (!$this->redirectEntity) {
                $this->redirect('/admin/redirects');

                return;
            }

            $this->isEditMode = true;

            breadcrumb()
                ->add(__('def.admin_panel'), url('/admin'))
                ->add(__('admin-redirects.title'), url('/admin/redirects'))
                ->add(__('admin-redirects.modal.edit_title'));

            $this->name = __('admin-redirects.modal.edit_title');
        } else {
            breadcrumb()
                ->add(__('def.admin_panel'), url('/admin'))
                ->add(__('admin-redirects.title'), url('/admin/redirects'))
                ->add(__('admin-redirects.modal.create_title'));

            $this->name = __('admin-redirects.modal.create_title');
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.cancel'))
                ->redirect('/admin/redirects')
                ->type(Color::OUTLINE_PRIMARY),

            Button::make($this->isEditMode ? __('def.save') : __('admin-redirects.buttons.add'))
                ->method('save')
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold'),
        ];
    }

    public function layout(): array
    {
        $fromUrl = request()->input('from_url', $this->redirectEntity?->fromUrl ?? '');
        $toUrl = request()->input('to_url', $this->redirectEntity?->toUrl ?? '');

        $layouts = [];

        $conflictInfo = $this->detectRouteConflict($fromUrl);
        if ($conflictInfo) {
            $layouts[] = LayoutFactory::view('admin-redirects::components.route-conflict-alert', [
                'conflictRoute' => $conflictInfo['route'],
                'conflictMessage' => $conflictInfo['message'],
            ]);
        }

        $layouts[] = LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('from_url')
                        ->type('text')
                        ->placeholder(__('admin-redirects.fields.from_url.placeholder'))
                        ->value($fromUrl)
                )
                    ->label(__('admin-redirects.fields.from_url.label'))
                    ->required()
                    ->small(__('admin-redirects.fields.from_url.help')),

                LayoutFactory::field(
                    Input::make('to_url')
                        ->type('text')
                        ->placeholder(__('admin-redirects.fields.to_url.placeholder'))
                        ->value($toUrl)
                )
                    ->label(__('admin-redirects.fields.to_url.label'))
                    ->required()
                    ->small(__('admin-redirects.fields.to_url.help')),
            ]),
        ]);

        $groups = $this->buildConditionGroupsData();

        $layouts[] = LayoutFactory::view('admin-redirects::components.conditions-editor', [
            'groups' => $groups,
            'conditionTypes' => $this->getConditionTypes(),
            'operators' => $this->getOperators(),
        ]);

        return $layouts;
    }

    public function save()
    {
        $data = request()->input();

        $fromUrl = $data['from_url'] ?? '';
        $toUrl = $data['to_url'] ?? '';

        if (empty($fromUrl)) {
            $this->flashMessage(__('admin-redirects.messages.from_url_required'), 'error');

            return;
        }

        if (empty($toUrl)) {
            $this->flashMessage(__('admin-redirects.messages.to_url_required'), 'error');

            return;
        }

        $validation = $this->validate([
            'from_url' => ['required', 'string', 'max-str-len:255'],
            'to_url' => ['required', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        if ($fromUrl === $toUrl) {
            $this->flashMessage(__('admin-redirects.messages.same_urls'), 'error');

            return;
        }

        $conflictInfo = $this->detectRouteConflict($fromUrl);
        if ($conflictInfo) {
            $this->flashMessage($conflictInfo['message'], 'warning');
        }

        try {
            $em = new \Cycle\ORM\EntityManager(orm());

            if ($this->isEditMode) {
                // Delete old conditions via EntityManager
                foreach ($this->redirectEntity->conditionGroups as $group) {
                    foreach ($group->conditions as $condition) {
                        $em->delete($condition);
                    }
                    $em->delete($group);
                }

                $this->redirectEntity->fromUrl = $fromUrl;
                $this->redirectEntity->toUrl = $toUrl;
                $this->redirectEntity->removeConditions();
                $em->persist($this->redirectEntity);

                $this->persistConditions($em, $this->redirectEntity, $data);

                $em->run();

                $this->flashMessage(__('admin-redirects.messages.update_success'), 'success');
            } else {
                $redirect = new Redirect($fromUrl, $toUrl);
                $em->persist($redirect);

                $this->persistConditions($em, $redirect, $data);

                $em->run();

                $this->flashMessage(__('admin-redirects.messages.save_success'), 'success');
            }

            $this->clearCache();
            $this->redirectTo('/admin/redirects', 300);
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    protected function buildConditionGroupsData(): array
    {
        if (!$this->redirectEntity || empty($this->redirectEntity->conditionGroups)) {
            return [];
        }

        $groups = [];
        foreach ($this->redirectEntity->conditionGroups as $group) {
            $conditions = [];
            foreach ($group->conditions as $condition) {
                $conditions[] = [
                    'type' => $condition->type,
                    'operator' => $condition->operator,
                    'value' => $condition->value,
                ];
            }
            if (empty($conditions)) {
                $conditions[] = ['type' => '', 'operator' => '', 'value' => ''];
            }
            $groups[] = $conditions;
        }

        return $groups;
    }

    protected function getConditionTypes(): array
    {
        return [
            'ip' => __('admin-redirects.condition_types.ip'),
            'cookie' => __('admin-redirects.condition_types.cookie'),
            'referer' => __('admin-redirects.condition_types.referer'),
            'request_method' => __('admin-redirects.condition_types.request_method'),
            'user_agent' => __('admin-redirects.condition_types.user_agent'),
            'header' => __('admin-redirects.condition_types.header'),
            'lang' => __('admin-redirects.condition_types.lang'),
        ];
    }

    protected function getOperators(): array
    {
        return [
            'equals' => __('admin-redirects.operators.equals'),
            'not_equals' => __('admin-redirects.operators.not_equals'),
            'contains' => __('admin-redirects.operators.contains'),
            'not_contains' => __('admin-redirects.operators.not_contains'),
        ];
    }

    protected function detectRouteConflict(string $fromUrl): ?array
    {
        if (empty($fromUrl)) {
            return null;
        }

        try {
            $routes = router()->getRoutes();

            foreach ($routes as $name => $route) {
                $routePath = $route->getPath();

                if ($routePath === $fromUrl || $routePath === rtrim($fromUrl, '/')) {
                    $routeName = $name ?: $routePath;

                    return [
                        'route' => $routeName,
                        'message' => __('admin-redirects.messages.route_conflict', [
                            'url' => $fromUrl,
                            'route' => $routeName,
                        ]),
                    ];
                }
            }
        } catch (Throwable $e) {
        }

        return null;
    }

    protected function persistConditions(\Cycle\ORM\EntityManager $em, Redirect $redirect, array $data): void
    {
        // Try JSON first, fallback to individual fields
        $parsedGroups = [];
        $json = $data['conditions_json'] ?? '';

        if (!empty($json)) {
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                $parsedGroups = $parsed;
            }
        }

        if (empty($parsedGroups)) {
            foreach ($data as $key => $value) {
                if (preg_match('/^conditions_(\d+)_(\d+)_(type|operator|value)$/', $key, $m)) {
                    $parsedGroups[(int) $m[1]][(int) $m[2]][$m[3]] = $value;
                }
            }
            ksort($parsedGroups);
            foreach ($parsedGroups as &$c) {
                ksort($c);
                $c = array_values($c);
            }
            unset($c);
            $parsedGroups = array_values($parsedGroups);
        }

        foreach ($parsedGroups as $conditions) {
            if (!is_array($conditions)) {
                continue;
            }

            $validConditions = [];
            foreach ($conditions as $cond) {
                $type = trim($cond['type'] ?? '');
                $operator = trim($cond['operator'] ?? '');
                if ($type !== '' && $operator !== '') {
                    $validConditions[] = $cond;
                }
            }

            if (empty($validConditions)) {
                continue;
            }

            $group = new ConditionGroup();
            $group->redirect = $redirect;

            foreach ($validConditions as $condData) {
                $condition = new RedirectCondition(
                    $condData['type'],
                    $condData['value'] ?? '',
                    $condData['operator']
                );
                $group->addCondition($condition);
            }

            $redirect->addConditionGroup($group);
            $em->persist($group);
        }
    }

    protected function clearCache(): void
    {
        try {
            cache()->delete('flute.redirects.all');
        } catch (Throwable $e) {
        }
    }

}
