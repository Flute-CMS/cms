<?php

return array (
  'remember_me' => true,
  'remember_me_duration' => 604800,
  'csrf_enabled' => false,
  'reset_password' => true,
  'security_token' => true,
  'only_social' => false,
  'registration' => 
  array (
    'confirm_email' => false,
    'social_supplement' => false,
  ),
  'validation' => 
  array (
    'login' => 
    array (
      'min_length' => 4,
      'max_length' => 20,
    ),
    'password' => 
    array (
      'min_length' => 4,
      'max_length' => 30,
    ),
    'name' => 
    array (
      'min_length' => 2,
      'max_length' => 30,
    ),
  ),
);