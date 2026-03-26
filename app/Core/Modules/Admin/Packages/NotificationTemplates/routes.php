<?php

use Flute\Admin\Packages\NotificationTemplates\Screens\NotificationBroadcastScreen;
use Flute\Admin\Packages\NotificationTemplates\Screens\NotificationTemplateEditScreen;
use Flute\Admin\Packages\NotificationTemplates\Screens\NotificationTemplateListScreen;
use Flute\Core\Router\Router;

Router::screen('/admin/notification-templates', NotificationTemplateListScreen::class);
Router::screen('/admin/notification-templates/{id}/edit', NotificationTemplateEditScreen::class);
Router::screen('/admin/notification-broadcast', NotificationBroadcastScreen::class);
