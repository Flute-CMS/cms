<?php

return [
    'admin_settings' => [
        'title' => [
            'admin_header' => '系统设置',
            'system' => '系统',
            'authorization' => '授权',
            'databases' => '数据库',
            'what_is_this' => '这是什么？',
            'default_db' => '默认数据库',
            'debug' => '调试',
            'connections_dbs' => '连接和数据库',
            'connections' => '连接',
            'in_short' => '简而言之',
            'dbs' => '数据库',
            'language' => '语言',
            'mail_server' => '邮件服务器',
            'profile' => '个人资料',
            'replenishment' => '充值',
            'summing_up' => '总结',
        ],
        'description' => [
            'system_settings_intro' => '让我们来看看系统设置是什么以及如何处理它们。',
            'system_settings_details' => '在此部分，您可以更改基本引擎设置。更改关键点会产生重大影响，因此只修改您确定的内容。',
            'authorization_settings' => '在这里，您可以更改授权设置（会话时间等）。我认为这很清楚。',
            'databases_overview' => '现在让我们更仔细地看一下数据库。',
            'database_principles' => '引擎中数据库的原则安排有些非标准，让我们深入了解一下。',
            'default_db_usage' => '此部分负责 Flute 将使用的数据库。最好根本不要触摸或更改此部分。',
            'debug_mode_info' => '此部分启用调试模式用于数据库。建议只由开发人员使用。',
            'multiple_connections_dbs' => 'Flute 使用多个连接和多个数据库系统。',
            'connections_info' => '连接是连接到数据库的数据（登录名、密码等），而数据库本身是用于模块和引擎的数据。',
            'connections_dbs_summary' => '可以有一个连接（如果所有内容都在一个数据库中）。还可以有许多数据库。总的来说，您以后会明白一切的。',
            'managing_connections' => '此部分允许您操作 Flute 中的所有连接。',
            'setting_up_dbs' => '在此部分，对使用连接的数据库进行设置和安装。呼，深呼吸，让我们继续...',
            'language_settings' => '在此部分，您选择默认的引擎语言并缓存翻译。',
            'mail_server_settings' => '在此部分，您将配置用于发送电子邮件的邮件服务器（用于重置密码等）。',
            'profile_settings' => '在这里，您可以为用户配置各种个人资料参数。只需访问，您就能自己理解一切。',
            'balance_replenishment_settings' => '此部分包含用于余额充值的设置。无论是显示的货币还是最低充值金额。',
            'tour_ending' => '不可思议，但我们已经结束了主要要点的导览。我知道，您需要消化一下，但我相信您一定能够应付！'
        ]
    ],
    'admin_stats' => [
        'title' => [
            'sidebar' => '侧边栏',
            'main_menu' => '主菜单',
            'additional_menu' => '附加菜单',
            'recent_menu' => '最近访问的页面',
            'sidebar_complete' => '侧边栏完成',
            'navbar' => '导航栏',
            'search' => '搜索',
            'version' => '版本',
            'report_generation' => '报告生成',
            'final' => '就这样了！',
        ],
        'description' => [
            'sidebar' => '让我们来看看管理员面板的主要导航面板。好的，让我们开始吧！',
            'main_menu' => '此菜单包含与关键系统设置相关的项目。',
            'additional_menu' => '此菜单包含模块和各种系统组件的项目。在使用时，您将了解每个项目负责什么。',
            'recent_menu' => '在管理员面板底部还显示了您最近访问的页面。如果您经常访问某个页面而不想经常搜索它，这可能会有所帮助。',
            'sidebar_complete' => '我们已经完成了侧边栏，现在让我们继续处理其他组件。',
            'navbar' => '让我们看看顶部面板，那里有什么以及如何展示。',
            'search' => '此输入字段允许您通过关键字查找所需的项目或页面。只需开始键入，搜索即可为您提供所需的结果！',
            'version' => '此处显示已安装引擎的版本。此外，将来将可以直接从管理员面板更新引擎。',
            'report_generation' => '此按钮允许您生成有关系统的详细报告。如果您需要向某人发送有关系统错误的信息，此存档应随消息一起发送。',
            'final' => '我已向您介绍了管理员面板内的主要组件。在不同的页面上，您会找到其他提示。祝您使用愉快！'
        ]
    ],
    'home' => [
        'title' => [
            'editor_mode_title' => '编辑模式',
            'editor_title' => '可编辑页面的标题',
            'editor_area' => '编辑器区域',
            'editor_toolbar' => '编辑器工具',
            'save_button' => '保存',
            'editor_course_completed' => '编辑器课程完成',
        ],
        'description' => [
            'editor_mode' => 'CMS 为每个页面提供了编辑模式，允许我们对其进行完全定制。',
            'editor_title' => '这里是将要编辑的页面的标题。',
            'editor_area' => '您可以在此处创建 各种区块、小部件和文本，这些将显示在编辑器中。',
            'editor_toolbar' => '在左侧，您可以添加或修改现有的区块。',
            'save_button' => '修改数据后，请务必点击此按钮保存所有内容。',
            'editor_course_completed' => '这就是您在编辑器中基本理解所需的全部内容。有关更多详细信息，请参阅官方文档。',
        ],
    ],
];
