<?php

return [
    "back" => "返回",
    "next" => "下一个",
    "last_step_required" => "要继续进行，您需要完成上一步！",
    "finish" => "完成安装！",
    1 => [
        'card_head' => '语言选择',
        "title" => "Flute :: 语言选择",
        'Несуществующий язык' => '看起来您选择了一些神秘的语言 :0'
    ],
    2 => [
        "title" => "Flute :: 要求检查",
        'card_head' => "兼容性",
        'card_head_desc' => "在此页面，您需要检查所有要求的兼容性，如果一切正常，则继续安装",
        'req_not_completed' => "未满足要求",
        'need_to_install' => "需要安装",
        'may_installed' => "建议安装",
        'installed' => "已安装",
        'all_good' => "一切正常！",
        'may_unstable' => "可能工作不稳定",
        'min_php_7' => "最低 PHP 版本为 7.4！",
        'php_exts' => "PHP 扩展",
        'other' => '其他'
    ],
    3 => [
        "title" => "Flute :: 数据库输入",
        'card_head' => "数据库连接",
        'card_head_desc' => "使用数据库中的数据填写所有字段。最好创建一个新的数据库。",
        "driver" => "选择数据库驱动程序",
        "ip" => "输入数据库主机",
        "port" => "输入数据库端口",
        "db" => "输入数据库名称",
        "user" => "输入数据库用户",
        "pass" => "输入数据库密码",
        'db_error' => "连接时出现错误：<br>%error%",
        'data_invalid' => "输入的数据无效！",
        "check_data" => "检查数据",
        "data_correct" => '数据正确'
    ],
    4 => [
        "title" => "Flute :: 数据迁移",
        'card_head' => "数据迁移",
        'card_head_desc' => "您是否需要从其他 CMS 迁移数据。选择所需的 CMS（如果需要）",
        'migrate_from' => '从哪里迁移数据',
        'thanks_but_no' => '谢谢，但不需要',
        'card_head_2' => '从 %cms% 迁移数据',
        'card_desc_2' => '选择所需的迁移类型，并填写表单中的数据',
        'migrate' => [
            'all' => '全部迁移',
            'servers' => '迁移服务器',
            'admins' => '迁移管理员',
            'gateways' => '迁移支付网关',
            'payments' => '迁移支付记录',
        ]
    ],
    5 => [
        "title" => "Flute :: 所有者注册",
        'card_head' => "所有者注册",
        'card_head_desc' => "使用数据填写所有字段以创建您的帐户。",
        'login' => '登录',
        'login_placeholder' => '输入登录名',
        'name' => '昵称',
        'name_placeholder' => '输入显示名称',
        'email' => '电子邮件',
        'email_placeholder' => '输入电子邮件',
        'password' => '密码',
        'password_placeholder' => '输入密码',
        'repassword' => '重新输入密码',
        'repassword_placeholder' => '再次输入密码',
        'login_length' => '登录名长度至少为 2 个字母！',
        'name_length' => '昵称长度至少为 2 个字母！',
        'pass_length' => '密码长度至少为 4 个字符！',
        'invalid_email' => '请输入正确的电子邮件！',
        'pass_diff' => '输入的密码不匹配！',
        'error_create_user' => '创建用户时出错！',
    ],
    6 => [
        "title" => "Flute :: 是否启用工具提示？",
        'card_head' => "启用工具提示",
        'card_head_desc' => "您是否需要在引擎中启用工具提示，以了解如何使用某些功能？",
        'yes' => '是的，启用，我是第一次来这里（推荐）🤯',
        'no' => '不，我已经到处转这个 Flute 了 😎'
    ],
    7 => [
        "title" => "Flute :: 错误报告",
        'card_head' => "启用错误报告",
        'card_head_desc' => "如果引擎发生故障，错误将被发送到我们的服务器进行处理。一段时间后，可能会因为您的帮助而发布带有修复的更新 🥰",
        'yes' => '是的，发送错误以提高引擎性能 😇',
        'no' => '不，不发送任何东西，我对此不感兴趣 🤐'
    ],
];
