<?php

return [
    "back" => "Back",
    "next" => "Next",
    "last_step_required" => "To proceed further, you need to complete the last step!",
    "finish" => "Finish Installation!",
    "1" => [
        'card_head' => 'Language Selection',
        "title" => "Flute :: Language Selection",
        'Несуществующий язык' => 'Looks like you selected some mysterious language :0'
    ],
    2 => [
        "title" => "Flute :: Requirements Check",
        'card_head' => "Compatibility",
        'card_head_desc' => "On this page, you need to check the compliance of all requirements, and if everything is good, then proceed with the installation",
        'req_not_completed' => "Requirements not met",
        'need_to_install' => "Need to install",
        'may_installed' => "Recommended to install",
        'installed' => "Installed",
        'all_good' => "All good!",
        'may_unstable' => "May work unstable",
        'min_php_7' => "Minimum PHP version is 7.4!",
        'php_exts' => "PHP Extensions",
        'other' => 'Other'
    ],
    3 => [
        "title" => "Flute :: Database Input",
        'card_head' => "Database Connection",
        'card_head_desc' => "Fill in all the fields with data from your database. It is preferable to create a new database.",
        "driver" => "Select Database Driver",
        "ip" => "Enter Database Host",
        "port" => "Enter Database Port",
        "db" => "Enter Database Name",
        "user" => "Enter Database User",
        "pass" => "Enter Database Password",
        'db_error' => "An error occurred while connecting: <br>%error%",
        'data_invalid' => "The data entered is invalid!",
        "check_data" => "Check Data",
        "data_correct" => 'Data Correct'
    ],
    4 => [
        "title" => "Flute :: Data Migration",
        'card_head' => "Data Migration",
        'card_head_desc' => "Do you need to migrate data from other CMS. Select the required CMS (if necessary)",
        'migrate_from' => 'Migrate Data From',
        'thanks_but_no' => 'Thanks, but no',
        'card_head_2' => 'Data Migration from %cms%',
        'card_desc_2' => 'Select the required types of migration and fill in the data in the form',
        'migrate' => [
            'all' => 'Migrate All',
            'servers' => 'Migrate Servers',
            'admins' => 'Migrate Admins',
            'gateways' => 'Migrate Payment Gateways',
            'payments' => 'Migrate Payment History',
        ]
    ],
    5 => [
        "title" => "Flute :: Owner Registration",
        'card_head' => "Owner Registration",
        'card_head_desc' => "Fill in all fields with data to create your account.",
        'login' => 'Login',
        'login_placeholder' => 'Enter login',
        'name' => 'Nickname',
        'name_placeholder' => 'Enter display name',
        'email' => 'Email',
        'email_placeholder' => 'Enter Email',
        'password' => 'Password',
        'password_placeholder' => 'Enter password',
        'repassword' => 'Re-enter password',
        'repassword_placeholder' => 'Enter password again',
        'login_length' => 'Minimum login length is 2 letters!',
        'name_length' => 'Minimum nickname length is 2 letters!',
        'pass_length' => 'Minimum password length is 4 characters!',
        'invalid_email' => 'Enter email correctly!',
        'pass_diff' => 'Entered passwords do not match!',
        'error_create_user' => 'Error creating user!',
    ],
    6 => [
        "title" => "Flute :: Are Tooltips Enabled?",
        'card_head' => "Enabling Tooltips",
        'card_head_desc' => "Do you need tooltips in the engine to understand how to use certain functionality?",
        'yes' => 'Yes, enable, I am here for the first time (recommended) 🤯',
        'no' => 'No, I\'ve been spinning this Flute everywhere 😎'
    ],
    7 => [
        "title" => "Flute :: Error Reporting",
        'card_head' => "Enabling Error Reporting",
        'card_head_desc' => "In case of malfunctioning of the engine, errors will be sent to our server for processing. After some time, an update with a fix may be released thanks to you 🥰",
        'yes' => 'Yes, send errors to improve engine performance 😇',
        'no' => 'No, don\'t send anything, I\'m not interested in it 🤐'
    ],
];
