<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;
use Nette\Utils\Strings;
use Symfony\Component\HttpFoundation\Response;

class SocialsView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.gateways');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table();
        $socials = rep(SocialNetwork::class)->findAll();

        $table->addColumns([
            (new TableColumn('id')),
            (new TableColumn('icon', ''))->setRender(
                '{{ICON_RENDER}}',
                "function(data, type, full, meta) {
                    let doc = new DOMParser().parseFromString(data, 'text/html');
                    let res = doc.documentElement.textContent;

                    let div = make('div');
                    div.innerHTML = res;
                    div.classList.add('icon-div');

                    return div;
                }"
            )->setOrderable(false)->setSearchable(false),
            (new TableColumn('key', __('admin.socials.key'))),
            (new TableColumn('enabled', __('def.status')))->setRender(
                '{{ RENDER_STATUS }}',
                "function(data, type, full, meta) {
                    let div = make('div');
                    let status = data == 1 ? 'active' : 'disabled';
                    div.classList.add('table-status', status);
                    div.innerHTML = translate(`def.`+status)
                    return div;
                }"
            ),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ SOCIALS_BUTTONS }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[3] == 1 ? "active" : "disabled";
    
                    let btnContainer = make("div");
                    btnContainer.classList.add("social-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-translate", "admin.socials.delete");
                    deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    deleteDiv.setAttribute("data-deletesocial", data[0]);
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-translate", "admin.socials.change");
                    changeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                    changeDiv.setAttribute("href", u(`admin/socials/edit/${data[0]}`));
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);

                    if (status === "active") {
                        let disableDiv = make("div");
                        disableDiv.classList.add("action-button", "disable");
                        disableDiv.setAttribute("data-translate", "admin.socials.disable_social");
                        disableDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        disableDiv.setAttribute("data-disablesocial", data[0]);
                        let disableIcon = make("i");
                        disableIcon.classList.add("ph-bold", "ph-power");
                        disableDiv.appendChild(disableIcon);
                        btnContainer.appendChild(disableDiv);
                    }
        
                    // Включить модуль
                    if (status === "disabled") {
                        let activeDiv = make("div");
                        activeDiv.classList.add("action-button", "activate");
                        activeDiv.setAttribute("data-translate", "admin.socials.enable_social");
                        activeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        activeDiv.setAttribute("data-activatesocial", data[0]);
                        let activeIcon = make("i");
                        activeIcon.classList.add("ph-bold", "ph-power");
                        activeDiv.appendChild(activeIcon);
                        btnContainer.appendChild(activeDiv);
                    }
    
                    return btnContainer.outerHTML;
                }
                '
            ]
        ]);

        $table->setData($socials);

        return view("Core/Admin/Http/Views/pages/socials/list", [
            "socials" => $table->render()
        ]);
    }

    public function add(FluteRequest $request): Response
    {
        return view("Core/Admin/Http/Views/pages/socials/add", [
            'drivers' => $this->getAllDrivers()
        ]);
    }

    public function edit(FluteRequest $request, string $id): Response
    {
        $social = $this->getSocialNetwork((int) $id);

        if (!$social)
            return $this->error(__('admin.socials.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/socials/edit", [
            'social' => $social,
            'drivers' => $this->getAllDrivers($social->key)
        ]);
    }

    protected function getSocialNetwork(int $id): ?SocialNetwork
    {
        return rep(SocialNetwork::class)->findByPK($id);
    }

    protected function getAllDrivers(?string $currentDriver = null)
    {
        $namespaceMap = app()->getLoader()->getPrefixesPsr4();
        $result = [];

        foreach ($namespaceMap as $namespace => $paths) {
            foreach ($paths as $path) {
                $fullPath = realpath($path);
                if ($fullPath && is_dir($fullPath)) {
                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath));
                    foreach ($files as $file) {
                        if ($file->isFile() && $file->getExtension() == 'php') {
                            $class = $namespace . str_replace('/', '\\', substr($file->getPathname(), strlen($fullPath), -4));

                            if (Strings::startsWith($class, 'Hybridauth\Provider')) {
                                $result[] = $class;
                            }
                        }
                    }
                }
            }
        }

        $namespaces = array_keys(app()->getLoader()->getClassMap());
        $result = array_merge(array_filter($namespaces, function ($item) {
            return Strings::startsWith($item, "Hybridauth\\Provider");
        }), $result);

        foreach ($result as $key => $item) {
            $ex = explode('\\', $item);
            $driver = $ex[array_key_last($ex)];

            $find = rep(SocialNetwork::class)->findOne([
                "key" => $driver
            ]);

            if (!$find || ($driver === $currentDriver))
                $result[$key] = $driver;
        }

        return $result;
    }
}