<?php

namespace Flute\Core\Admin\Builders;

use Flute\Core\Admin\AdminBuilder;
use Flute\Core\Admin\Contracts\AdminBuilderInterface;
use Flute\Core\App;
use Flute\Core\Template\Template;

/**
 * Класс, который должен расширять мой шаблонизатор для более удобной
 * разработки.
 */

class AdminThemeBuilder implements AdminBuilderInterface
{
    public function build(AdminBuilder $adminBuilder): void
    {
        /**
         * @var Template
         */
        $template = template();

        $blade = $template->getBlade();

        // Sidebar
        $blade->addInclude("Core/Admin/Http/Views/components/sidebar/index.blade.php", "admin_sidebar");
        $blade->addInclude("Core/Admin/Http/Views/components/sidebar/items.blade.php", "admin_sidebar_items");

        $blade->addInclude("Core/Admin/Http/Views/components/start_page.blade.php", "admin_start_page");
        
        // Navbar
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/index.blade.php", "admin_navbar");
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/items/contact.blade.php", "admin_navbar_contact");
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/items/logo.blade.php", "admin_navbar_logo");
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/items/search.blade.php", "admin_navbar_search");
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/items/version.blade.php", "admin_navbar_version");
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/items/log.blade.php", "admin_navbar_log");
        $blade->addInclude("Core/Admin/Http/Views/components/navbar/items/tabs.blade.php", "admin_navbar_tabs");

        $template->getTemplateAssets()->getCompiler()->setImportPaths(
            path('app/Core/Admin/Http/Views/assets/styles/')
        );

        $template->variables()->setAllToDefault();
    }
}