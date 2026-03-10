<script type="application/json" id="onboarding-data">
    <?php echo json_encode([
        'next' => __('admin-dashboard.onboarding.next'),
        'prev' => __('admin-dashboard.onboarding.prev'),
        'finish' => __('admin-dashboard.onboarding.finish'),
        'steps' => [
            [
                'id' => 'welcome',
                'title' => __('admin-dashboard.onboarding.welcome.title'),
                'text' => __('admin-dashboard.onboarding.welcome.text'),
            ],
            [
                'id' => 'sidebar',
                'title' => __('admin-dashboard.onboarding.sidebar.title'),
                'text' => __('admin-dashboard.onboarding.sidebar.text'),
                'attachTo' => '.sidebar__menu',
                'position' => 'right',
            ],
            [
                'id' => 'search',
                'title' => __('admin-dashboard.onboarding.search.title'),
                'text' => __('admin-dashboard.onboarding.search.text'),
                'attachTo' => '.sidebar__search-btn',
                'position' => 'bottom',
            ],
            [
                'id' => 'settings',
                'title' => __('admin-dashboard.onboarding.settings.title'),
                'text' => __('admin-dashboard.onboarding.settings.text'),
                'attachTo' => 'li[data-item-key="settings-group"]',
                'position' => 'right',
            ],
            [
                'id' => 'servers',
                'title' => __('admin-dashboard.onboarding.servers.title'),
                'text' => __('admin-dashboard.onboarding.servers.text'),
                'attachTo' => 'li[data-item-key="servers"]',
                'position' => 'right',
            ],
            [
                'id' => 'marketplace',
                'title' => __('admin-dashboard.onboarding.marketplace.title'),
                'text' => __('admin-dashboard.onboarding.marketplace.text'),
                'attachTo' => 'li[data-item-key="marketplace"]',
                'position' => 'right',
            ],
            [
                'id' => 'checklist',
                'title' => __('admin-dashboard.onboarding.checklist.title'),
                'text' => __('admin-dashboard.onboarding.checklist.text'),
                'attachTo' => '.dashboard-notices__checklist',
                'position' => 'top',
            ],
            [
                'id' => 'done',
                'title' => __('admin-dashboard.onboarding.done.title'),
                'text' => __('admin-dashboard.onboarding.done.text'),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE) ?>
</script>
