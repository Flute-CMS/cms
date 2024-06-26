<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Modules\ModuleManager;
use Flute\Core\Support\AbstractController;
use Flute\Core\Table\TableColumn;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\Response;

class ModulesView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.modules');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table();
        $modules = app(ModuleManager::class)->getModules();

        foreach ($modules as $module) {
            $module->module_json = Json::encode($module);

            $module->description = __($module->description);

            if ($module->created_at)
                $module->createdAt = $module->created_at->format(default_date_format());
        }

        $modules = $modules->toArray();

        $table->addColumns([
            (new TableColumn('key'))->setVisible(false),
            (new TableColumn('url'))->setVisible(false),
            (new TableColumn('module_json'))->setVisible(false),
            (new TableColumn('name', __('def.name')))
                ->setRender(
                    '{{RENDER_NAME}}',
                    'function(data, type, full, meta) {
                if( full[1]?.length ) {
                    let a = make("a");
                    a.setAttribute("href", full[1]);
                    a.setAttribute("target", "_blank");
                    a.innerHTML = data;
                    return a;
                }
                return data;
            }'
                ),
            (new TableColumn('description', __('def.description'))),
            (new TableColumn('installedVersion', __('admin.modules_list.installed_version')))->setType('text'),
            (new TableColumn('version', __('admin.modules_list.last_version')))->setType('text'),
            (new TableColumn('authors', __('admin.modules_list.authors')))
                ->setRender(
                    '{{RENDER_AUTHORS}}',
                    "function( data, type, full, meta ) { 
                    let container = make('div');
                    container.classList.add('chips-container');
                    let array = JSON.parse(data);

                    array.forEach(function(element) {
                        let div = document.createElement('div');
                        div.textContent = element;
                        div.classList.add('item');
                        container.appendChild(div);
                    });

                    return container;
                }"
                ),
            (new TableColumn("status", __('def.status')))->setRender(
                '{{ RENDER_STATUS }}',
                "function(data) {
                    let div = make('div');
                    div.classList.add('table-status', data);
                    div.innerHTML = translate(`def.`+data)
                    return div;
                }"
            ),
            (new TableColumn('createdAt', __('def.created_at')))->setDefaultOrder()->setDefaultOrderType('desc'),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ BUTTONS_MODULE }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[8], settings = [];
    
                    try {
                        settings = JSON.parse(data[2]);
                    } catch(e) {
                        //
                    }
    
                    let btnContainer = make("div");
                    btnContainer.classList.add("module-action-buttons");

                    if( data[5] !== data[6] && status !== "notinstalled" ) {
                        let updateContainerDiv = make("div");
                        updateContainerDiv.classList.add("action-button-update");
                        let updateDiv = make("div");
                        updateDiv.classList.add("action-button", "update");
                        updateDiv.setAttribute("data-translate", "admin.modules_list.update_module");
                        updateDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        updateDiv.setAttribute("data-tooltip-conf", "left");
                        updateDiv.setAttribute("data-updatemodule", data[0]);
                        let updateIcon = make("i");
                        updateIcon.classList.add("ph", "ph-rocket-launch");
                        updateDiv.appendChild(updateIcon);
                        updateContainerDiv.appendChild(updateDiv);
                        btnContainer.appendChild(updateContainerDiv);
                    }
        
                    // Установить модуль (icon: download)
                    if (status === "notinstalled") {
                        let installDiv = make("div");
                        installDiv.classList.add("action-button", "install");
                        installDiv.setAttribute("data-translate", "admin.modules_list.install_module");
                        installDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        installDiv.setAttribute("data-installmodule", data[0]);
                        installDiv.setAttribute("data-tooltip-conf", "left");
                        let installIcon = make("i");
                        installIcon.classList.add("ph-bold", "ph-download");
                        installDiv.appendChild(installIcon);
                        btnContainer.appendChild(installDiv);
                    }
                    
                    // Удалить модуль (icon: trash)
                    if (["notinstalled", "disabled"].includes(status)) {
                        let deleteDiv = make("div");
                        deleteDiv.classList.add("action-button", "delete");
                        deleteDiv.setAttribute("data-translate", "admin.modules_list.delete_module");
                        deleteDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        deleteDiv.setAttribute("data-tooltip-conf", "left");
                        deleteDiv.setAttribute("data-deleteaction", data[0]);
                        deleteDiv.setAttribute("data-deletepath", "modules");
                        let deleteIcon = make("i");
                        deleteIcon.classList.add("ph-bold", "ph-trash");
                        deleteDiv.appendChild(deleteIcon);
                        btnContainer.appendChild(deleteDiv);
                    }
        
                    // if (Object.keys(settings).length > 0 && status !== "notinstalled") {
                    //     let settingsDiv = make("div");
                    //     settingsDiv.classList.add("action-button", "settings");
                    
                    //     settingsDiv.setAttribute("data-translate", "def.settings");
                    //     settingsDiv.setAttribute("data-translate-attribute", "data-tooltip");

                    //     settingsDiv.setAttribute("data-settingsmodule", data[2]);
                    //     settingsDiv.setAttribute("data-key", data[0]);
                    //     settingsDiv.setAttribute("data-tooltip-conf", "left");
                    //     let gearIcon = make("i");
                    //     gearIcon.classList.add("ph", "ph-gear");
                    //     settingsDiv.appendChild(gearIcon);
                    //     btnContainer.appendChild(settingsDiv);
                    // }
    
        
                    // Отключить модуль (icon: power)
                    if (status === "active") {
                        let disableDiv = make("div");
                        disableDiv.classList.add("action-button", "disable");
                        disableDiv.setAttribute("data-translate", "admin.modules_list.disable_module");
                        disableDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        disableDiv.setAttribute("data-disablemodule", data[0]);
                        disableDiv.setAttribute("data-tooltip-conf", "left");
                        let disableIcon = make("i");
                        disableIcon.classList.add("ph-bold", "ph-power");
                        disableDiv.appendChild(disableIcon);
                        btnContainer.appendChild(disableDiv);
                    }
        
                    // Включить модуль
                    if (status === "disabled") {
                        let activeDiv = make("div");
                        activeDiv.classList.add("action-button", "activate");
                        activeDiv.setAttribute("data-translate", "admin.modules_list.enable_module");
                        activeDiv.setAttribute("data-translate-attribute", "data-tooltip");
                        activeDiv.setAttribute("data-activatemodule", data[0]);
                        activeDiv.setAttribute("data-tooltip-conf", "left");
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

        $table->setData($modules);

        return view("Core/Admin/Http/Views/pages/modules/list", [
            "modules" => $table->render()
        ]);
    }

    public function catalog(): Response
    {
        //

        return $this->success();
    }
}