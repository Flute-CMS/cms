<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\composer;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\PaymentGateway;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Support\AbstractController;
use Flute\Core\Table\TableColumn;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

class ComposerView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.composer');
    }

    public function list(): Response
    {
        $table = table();

        $table->addColumns([
            (new TableColumn('name', __('admin.composer.name'))),
            (new TableColumn('installed', __('admin.composer.installed'))),
        ]);

        $data = [];

        $packages = json_decode(file_get_contents(BASE_PATH . 'composer.json'), true);

        foreach ($packages['require'] as $key => $val) {
            $data[] = [
                'name' => $key,
                'installed' => $val
            ];
        }

        $table->setData($data);
        $table->withDelete('composer');

        return view("Core/Admin/Http/Views/pages/composer/list", [
            'table' => $table->render()
        ]);
    }

    public function add(): Response
    {
        return view('Core/Admin/Http/Views/pages/composer/add', [
            'composerTable' => $this->getOmnipaysTable()
        ]);
    }

    protected function getOmnipaysTable()
    {
        $table = table(url('admin/api/composer/table'));

        $table->addColumns([
            (new TableColumn('name', __('admin.composer.packageName')))->setOrderable(false),
            (new TableColumn('description', __('admin.composer.description')))->setOrderable(false),
            (new TableColumn('url', "URL"))->setOrderable(false),
            (new TableColumn('downloads', __('admin.composer.downloads')))->setOrderable(false),
            (new TableColumn('', __('admin.composer.download')))->setOrderable(false)->setRender('{{DOWNLOAD}}', $this->downloadButton()),
        ]);

        return $table->render();
    }

    protected function downloadButton(): string
    {
        return '
            function(data, type, full) {
                let button = make("button");
                button.classList.add("action-button", "install", "composer-button", "btn", "primary", "size-s");
                button.setAttribute("data-install", full[0]);
                button.setAttribute("type", "button");
                button.innerHTML = translate("def.install");
                return button;
            }
        ';
    }
}