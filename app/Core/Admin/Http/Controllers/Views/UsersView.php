<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Cycle\ORM\Select\QueryBuilder;
use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Admin\Services\UserService;
use Flute\Core\Database\Entities\PaymentInvoice;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;
use Symfony\Component\HttpFoundation\Response;

class UsersView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.users');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table();
        $users = rep(User::class)->select()->load('roles')->fetchAll();

        foreach ($users as $key => $user) {
            $roles = $user->getRoles()->toArray();
            $user->roles_json = json_encode($roles);
            $user->user_url = url('profile/'.$user->getUrl())->get();
            $user->createdAt = $user->created_at->format(default_date_format());
            $user->lastLoggedPhrase = $this->getLastLoggedPhrase($user->last_logged);

            if (!user()->canEditUser($user)) {
                unset($users[$key]);
            }
        }

        $table->addColumn((new TableColumn('id', 'ID')));

        $table->addColumn((new TableColumn('user_url'))->setVisible(false));
        $table->addCombinedColumn('avatar', 'name', __('def.user'), 'user_url', true);

        $table->addColumns([
            (new TableColumn('login', __('def.user_login'))),
            (new TableColumn("roles_json", __('def.roles')))->setRender(
                '{{ RENDER_ROLES }}',
                "function(data) {
                    let container = make('div');
                    container.classList.add('chips-container');
                    let array = JSON.parse(data);

                    array.forEach(function(element) {
                        let div = document.createElement('div');
                        div.textContent = element.name;
                        div.classList.add('item');
                        div.style.backgroundColor = element.color;
                        container.appendChild(div);
                    });

                    return container;
                }"
            ),
            (new TableColumn('createdAt', __('admin.users.created'))),
            (new TableColumn('lastLoggedPhrase', __('admin.users.last_logged')))->setClean(false),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ USER_TEMPLATE }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[0];

                    let btnContainer = make("div");
                    btnContainer.classList.add("user-action-buttons");

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-translate", "admin.users.change");
                    changeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    changeDiv.setAttribute("href", u(`admin/users/edit/${data[0]}`));
                    changeDiv.setAttribute("data-tooltip-conf", "left");
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-translate", "admin.users.delete");
                    deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    deleteDiv.setAttribute("data-deleteaction", data[0]);
                    deleteDiv.setAttribute("data-deletepath", "users");
                    deleteDiv.setAttribute("data-tooltip-conf", "left");
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);
        
                    return btnContainer.outerHTML;
                }
                '
            ]
        ]);

        $table->setData($users);

        return view("Core/Admin/Http/Views/pages/users/list", [
            "users" => $table->render()
        ]);
    }

    private function getLastLoggedPhrase($lastLoggedDateTime): string
    {
        if( !$lastLoggedDateTime ) {
            return "-";
        }

        $now = new \DateTime();
        $interval = $now->getTimestamp() - $lastLoggedDateTime->getTimestamp();

        if ($interval <= 600) {
            return '<div class="table-status active">'.__('def.online').'</div>';
        } elseif ($interval <= 3600) {
            return __('def.was_in_hour');
        } else {
            return __('def.was_in_online', [
                ':time' => $lastLoggedDateTime->format(default_date_format())
            ]);
        }
    }

    public function edit(FluteRequest $request, string $id, UserService $userService): Response
    {
        $user = rep(User::class)->select()->load([
            'socialNetworks',
            'socialNetworks.socialNetwork',
            'roles',
            'userDevices',
            'invoices.promoCode',
            'invoices.currency'
        ])
            ->load('actionLogs', [
                'load' => function (QueryBuilder $qb) {
                    $qb->orderBy('action_date', 'desc');
                    $qb->limit(15);
                }
            ])
            ->load('invoices', [
                'load' => function (QueryBuilder $qb) {
                    $qb->where('is_paid', true);
                    $qb->orderBy('paid_at', 'desc');
                    $qb->limit(15);
                }
            ])
            ->fetchOne([
                'id' => $id,
            ]);

        if (!$user)
            return $this->error(__('admin.users.not_found'), 404);

        $canEdit = $userService->canEditUser(user()->getCurrentUser(), $user);

        if (!$canEdit)
            return $this->error(__('admin.users.permission_error'), 403);

        $roles = rep(Role::class)->findAll();

        return view("Core/Admin/Http/Views/pages/users/edit", [
            'user' => $user,
            "roles" => $roles,
        ]);
    }
}