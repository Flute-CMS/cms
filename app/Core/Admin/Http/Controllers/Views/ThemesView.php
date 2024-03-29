<?php

namespace Flute\Core\Admin\Http\Controllers\Views;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Table\TableColumn;
use Flute\Core\Theme\ThemeManager;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\Response;

class ThemesView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.templates');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function list(): Response
    {
        $table = table();
        $themes = app(ThemeManager::class)->getAllThemes();

        foreach( $themes as $theme ) {
            $theme->theme_settings = Json::encode($theme->settings->toArray());
        }

        $table->addColumns([
            (new TableColumn('key'))->setVisible(false),
            (new TableColumn('theme_settings'))->setVisible(false),
            (new TableColumn('name', __('def.name'))),
            (new TableColumn('description', __('def.description'))),
            (new TableColumn('version', __('def.version'))),
            (new TableColumn('author', __('def.author'))),
            (new TableColumn("status", __('def.status')))->setRender(
                '{{ RENDER_STATUS }}',
                "function(data) {
                    let div = make('div');
                    div.classList.add('table-status', data);
                    div.innerHTML = translate(`def.`+data)
                    return div;
                }"
            ),
            (new TableColumn())->setOrderable(false)
        ]);

        $table->addColumnDef([
            "targets" => -1,
            "data" => null,
            "render" => [
                'key' => '{{ BUTTONS_TEMPLATE }}',
                'js' => '
                function(data, type, full, meta) {
                    let status = data[6], settings = [];
    
                    try {
                        settings = JSON.parse(data[1]);
                    } catch(e) {
                        //
                    }

                    console.log(settings)
    
                    let btnContainer = make("div");
                    btnContainer.classList.add("theme-action-buttons");
        
                    // Удалить модуль (icon: trash)
                    if (["notinstalled", "disabled"].includes(status) && data[0] !== "standard") {
                        let deleteDiv = make("div");
                        deleteDiv.classList.add("action-button", "delete");
                        deleteDiv.setAttribute("data-tooltip", translate("admin.themes_list.delete_theme"));
                        deleteDiv.setAttribute("data-deletetheme", data[0]);
                        let deleteIcon = make("i");
                        deleteIcon.classList.add("ph-bold", "ph-trash");
                        deleteDiv.appendChild(deleteIcon);
                        btnContainer.appendChild(deleteDiv);
                    }
        
                    if (Object.keys(settings).length > 0 && status !== "notinstalled") {
                        let settingsDiv = make("div");
                        settingsDiv.classList.add("action-button", "settings");
                        settingsDiv.setAttribute("data-tooltip", translate("def.settings"));
                        settingsDiv.setAttribute("data-settingstheme", data[1]);
                        settingsDiv.setAttribute("data-key", data[0]);
                        let gearIcon = make("i");
                        gearIcon.classList.add("ph", "ph-gear");
                        settingsDiv.appendChild(gearIcon);
                        btnContainer.appendChild(settingsDiv);
                    }
    
                    // Установить модуль (icon: download)
                    if (status === "notinstalled") {
                        let installDiv = make("div");
                        installDiv.classList.add("action-button", "install");
                        installDiv.setAttribute("data-tooltip", translate("admin.themes_list.install_theme"));
                        installDiv.setAttribute("data-installtheme", data[0]);
                        let installIcon = make("i");
                        installIcon.classList.add("ph-bold", "ph-download");
                        installDiv.appendChild(installIcon);
                        btnContainer.appendChild(installDiv);
                    }
        
                    // Отключить модуль (icon: power)
                    if (status === "active") {
                        let disableDiv = make("div");
                        disableDiv.classList.add("action-button", "disable");
                        disableDiv.setAttribute("data-tooltip", translate("admin.themes_list.disable_theme"));
                        disableDiv.setAttribute("data-disabletheme", data[0]);
                        let disableIcon = make("i");
                        disableIcon.classList.add("ph-bold", "ph-power");
                        disableDiv.appendChild(disableIcon);
                        btnContainer.appendChild(disableDiv);
                    }
        
                    // Включить модуль
                    if (status === "disabled") {
                        let activeDiv = make("div");
                        activeDiv.classList.add("action-button", "activate");
                        activeDiv.setAttribute("data-tooltip", translate("admin.themes_list.enable_theme"));
                        activeDiv.setAttribute("data-activatetheme", data[0]);
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

        $table->setData($themes);

        return view("Core/Admin/Http/Views/pages/themes/list", [
            "themes" => $table->render()
        ]);
    }

    public function catalog(): Response
    {
        return $this->success();
    }
}