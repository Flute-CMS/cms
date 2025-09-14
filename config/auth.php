<?php

return array(
    'remember_me' => true,
    'remember_me_duration' => '2592000',
    'reset_password' => false,
    'security_token' => true,
    'only_social' => false,
    'only_modal' => true,
    'default_role' => null,
    'registration' =>
    array(
        'confirm_email' => false,
        'social_supplement' => false,
    ),
    'validation' =>
    array(
        'login' =>
        array(
            'min_length' => 4,
            'max_length' => 20,
        ),
        'password' =>
        array(
            'min_length' => 4,
            'max_length' => 30,
        ),
        'name' =>
        array(
            'min_length' => 2,
            'max_length' => 30,
        ),
    ),
    'check_ip' => true,
    'captcha' =>
    array(
        'enabled' =>
        array(
            'login' => false,
            'register' => false,
            'password_reset' => false,
        ),
        'type' => 'recaptcha_v2',
        'recaptcha' =>
        array(
            'site_key' => '',
            'secret_key' => '',
        ),
        'hcaptcha' =>
        array(
            'site_key' => '',
            'secret_key' => '',
        ),
    ),
);
