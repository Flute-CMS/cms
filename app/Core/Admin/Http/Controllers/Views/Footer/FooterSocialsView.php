<?php

namespace Flute\Core\Admin\Http\Controllers\Views\Footer;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\FooterSocial;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Table\TableColumn;

class FooterSocialsView extends AbstractController
{
    public function __construct()
    {
        HasPermissionMiddleware::permission('admin.footer');
        $this->middleware(HasPermissionMiddleware::class);
    }

    public function add(FluteRequest $request)
    {
        return view("Core/Admin/Http/Views/pages/footer/social/add");
    }

    public function list(FluteRequest $request)
    {
        $table = table();
        $socials = rep(FooterSocial::class)->findAll();

        $table->addColumns([
            (new TableColumn('id')),
            (new TableColumn('url'))->setVisible(false),
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
            (new TableColumn('name', __('def.name')))
                ->setRender(
                    '{{RENDER_NAME}}',
                    'function(data, type, full, meta) {
                if( full[1]?.length ) {
                    let a = make("a");
                    a.setAttribute("href", u(full[1]));
                    a.setAttribute("target", "_blank");
                    a.innerHTML = data;
                    return a;
                }
                return data;
            }'
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
                    let btnContainer = make("div");
                    btnContainer.classList.add("social-action-buttons");

                    let deleteDiv = make("div");
                    deleteDiv.classList.add("action-button", "delete");
                    deleteDiv.setAttribute("data-tooltip", translate("admin.footer.social_delete"));
                    deleteDiv.setAttribute("data-tooltip-conf", "left");
                    deleteDiv.setAttribute("data-deletesocial", data[0]);
                    let deleteIcon = make("i");
                    deleteIcon.classList.add("ph-bold", "ph-trash");
                    deleteDiv.appendChild(deleteIcon);
                    btnContainer.appendChild(deleteDiv);

                    let changeDiv = make("a");
                    changeDiv.classList.add("action-button", "change");
                    changeDiv.setAttribute("data-tooltip", translate("admin.footer.social_change"));
                    changeDiv.setAttribute("data-tooltip-conf", "left");
                    changeDiv.setAttribute("href", u(`admin/footer/socials/edit/${data[0]}`));
                    let changeIcon = make("i");
                    changeIcon.classList.add("ph", "ph-pencil");
                    changeDiv.appendChild(changeIcon);
                    btnContainer.appendChild(changeDiv);
    
                    return btnContainer.outerHTML;
                }
                '
            ]
        ]);

        $table->setData($socials);

        return view("Core/Admin/Http/Views/pages/footer/social/list", [
            'socials' => $table->render()
        ]);
    }

    public function edit(FluteRequest $request, string $id)
    {
        $item = rep(FooterSocial::class)->select()->where('id', (int) $id)->fetchOne();

        if (!$item)
            return $this->error(__('admin.footer.not_found'), 404);

        return view("Core/Admin/Http/Views/pages/footer/social/edit", [
            "item" => $item,
        ]);
    }
}