<?php

return array (
  'default' => 'default',
  'debug' => false,
  'databases' => 
  array (
    'default' => 
    array (
      'connection' => 'default',
      'prefix' => 'flute_',
    ),
  ),
  'connections' => 
  array (
    'default' => 
    array (
      'driver' => 'Spiral\\Database\\Driver\\MySQL\\MySQLDriver',
      'connection' => 'mysql:host=localhost;port=3306;dbname=',
      'username' => 'root',
      'password' => '',
    ),
  ),
);