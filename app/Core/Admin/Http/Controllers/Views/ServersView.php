<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;
use Symfony\Component\HttpFoundation\Response;

class ServersView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.servers');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table();
        $servers = rep(Server::class)->findAll();

        $table->addColumns([
            (new TableColumn('id')),
            (new TableColumn('name', __('def.name')))->setType('text'),
            (new TableColumn('mod', __('admin.servers.mod')))->setType('text'),
            (new TableColumn('ip', 'IP')),
            (new TableColumn('port', 'PORT'))->setType('text'),
            (new TableColumn('rcon', 'Rcon'))->setRender('{{RCON}}', '
            function(data, type, full, meta) {
                return `<span class="blurry-text">${data}</span>`;
            }
            '),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ SERVERS_BUTTONS }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[7], settings = [];
    
                    try {
                        settings = JSON.parse(data[2]);
                    } catch(e) {
                        //
                    }
    
                    let btnContainer = make("div");
                    btnContainer.classList.add("servers-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-tooltip", translate("admin.servers.delete"));
                    deleteDiv.setAttribute("data-deleteserver", data[0]);
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-tooltip", translate("admin.servers.change"));
                    changeDiv.setAttribute("href", u(`admin/servers/edit/${data[0]}`));
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);
    
                    return btnContainer.outerHTML;
                }
                '
            ]
        ]);

        $table->setData($servers);

        return view("Core/Admin/Http/Views/pages/servers/list", [
            "servers" => $table->render()
        ]);
    }

    public function add(FluteRequest $request): Response
    {
        return view("Core/Admin/Http/Views/pages/servers/add");
    }

    public function edit(FluteRequest $request, string $id): Response
    {
        $server = $this->getServer((int) $id);

        if (!$server)
            return $this->error(__('admin.servers.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/servers/edit", [
            'server' => $server
        ]);
    }

    protected function getServer(int $id)
    {
        return rep(Server::class)->findByPK($id);
    }
}