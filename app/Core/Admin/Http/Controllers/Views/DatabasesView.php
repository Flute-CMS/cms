<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Support\AbstractController;
use Flute\Core\Table\TableColumn;
use Symfony\Component\HttpFoundation\Response;

class DatabasesView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.system');
    }

    public function list(): Response
    {
        $table = table();

        $result = rep(DatabaseConnection::class)->select();

        $result = $result->fetchAll();

        foreach ($result as $key => $row) {
            // $result[$key]->mod = basename($row->mod);
            $result[$key]->server = ($result[$key]->server->id . ' - ' . $result[$key]->server->name);
        }

        $table->addColumns([
            (new TableColumn('id'))->setVisible(false),
            (new TableColumn('mod', __('admin.databases.mod'))),
            (new TableColumn('dbname', __('admin.databases.dbname'))),
            (new TableColumn('server', __('admin.databases.server'))),
        ])->withActions('databases');

        $table->setData($result);

        return view("Core/Admin/Http/Views/pages/databases/list", [
            'table' => $table->render()
        ]);
    }

    public function update($id): Response
    {
        $connection = rep(DatabaseConnection::class)->findByPK($id);

        if (!$connection)
            return $this->error(__("admin.databases.not_found"), 404);

        $params = json_decode($connection->additional, true);

        return view('Core/Admin/Http/Views/pages/databases/edit', [
            'connection' => $connection,
            'servers' => $this->getServers(),
            'mods' => $this->getMods(),
            'params' => $params
        ]);
    }

    public function add(): Response
    {
        return view('Core/Admin/Http/Views/pages/databases/add', [
            'servers' => $this->getServers(),
            'mods' => $this->getMods()
        ]);
    }

    protected function getServers(): array
    {
        return rep(Server::class)->select()->fetchAll();
    }

    protected function getMods(): array
    {
        return [
            __('admin.databases.stats') => [
                [
                    'name' => 'LevelsRanks',
                    'parameters' => [
                        [
                            'name' => 'table',
                            'label' => __('admin.databases.table'),
                            'type' => 'text',
                            'default' => 'base'
                        ],
                        [
                            'name' => 'ranks',
                            'label' => __('admin.databases.ranks'),
                            'type' => 'text',
                            'default' => "default"
                        ]
                    ]
                ],
                [
                    'name' => 'K4',
                ],
                [
                    'name' => 'FabiusRanks',
                    'parameters' => [
                        [
                            'name' => 'table',
                            'label' => __('admin.databases.table'),
                            'type' => 'text',
                        ],
                        [
                            'name' => 'ranks',
                            'label' => __('admin.databases.ranks'),
                            'type' => 'text',
                            'default' => "default"
                        ]
                    ]
                ],
            ],
            __('admin.databases.banscomms') => [
                [
                    'name' => 'MaterialAdmin',
                ],
                [
                    'name' => 'IKSAdmin',
                    'parameters' => [
                        [
                            'name' => 'sid',
                            'label' => __('admin.databases.sid'),
                            'type' => 'text'
                        ]
                    ]
                ],
                [
                    'name' => 'PisexAdmin',
                ],
                [
                    'name' => 'SimpleAdmin',
                ],
            ],
            __('admin.databases.other') => [
                [
                    'name' => 'VIP',
                    'parameters' => [
                        [
                            'name' => 'sid',
                            'label' => __('admin.databases.sid'),
                            'type' => 'number'
                        ]
                    ]
                ],
            ]
        ];
    }
}