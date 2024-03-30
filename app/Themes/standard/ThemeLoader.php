<?php

namespace Flute\Themes\standard;

use Flute\Core\Support\AbstractThemeLoader;

class ThemeLoader extends AbstractThemeLoader
{
    public function __construct()
    {
        $this->createTheme();
    }

    protected function createTheme()
    {
        $this->setKey("standard");
        $this->setName("Flute Standard");
        $this->setAuthor("Flames");
        $this->setDescription("Просто стандартный шаблон на Flute");
        $this->setVersion("1.1");

        $this->setSettings([
            "nav_type" => [
                'name' => 't_standard.nav_type',
                'description' => 't_standard.nav_type_desc',
                'value' => 'default'
            ]
        ]);

        // User select components
        $this->addComponentLayout('cookie_alert', 'components/alerts/cookie');
        $this->addComponentLayout('lang_alert', 'components/alerts/select_language');
        $this->addComponentLayout('mobile_alert', 'components/alerts/mobile');

        $this->addComponentLayout('editor', 'components/editor');
        $this->addComponentLayout('flash', 'components/flash');
        $this->addComponentLayout('footer', 'components/footer');
        $this->addComponentLayout('navbar', 'components/navbar');
        $this->addComponentLayout('navigation', 'components/navigation');

        $this->loadTranslations();
    }
}