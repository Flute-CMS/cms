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
    \Cycle\Database\Config\MySQLDriverConfig::__set_state(array(
       'options' => 
      array (
        'withDatetimeMicroseconds' => false,
        'logInterpolatedQueries' => false,
        'logQueryParameters' => false,
      ),
       'defaultOptions' => 
      array (
        'withDatetimeMicroseconds' => false,
        'logInterpolatedQueries' => false,
        'logQueryParameters' => false,
      ),
       'connection' => 
      \Cycle\Database\Config\MySQL\TcpConnectionConfig::__set_state(array(
         'nonPrintableOptions' => 
        array (
          0 => 'password',
          1 => 'PWD',
        ),
         'user' => 'root',
         'password' => '',
         'options' => 
        array (
          8 => 0,
          3 => 2,
          1002 => 'SET NAMES utf8mb4',
          17 => false,
        ),
         'port' => 3306,
         'database' => '',
         'host' => 'localhost',
         'charset' => 'utf8mb4',
      )),
       'driver' => 'Cycle\\Database\\Driver\\MySQL\\MySQLDriver',
       'reconnect' => true,
       'timezone' => 'UTC',
       'queryCache' => true,
       'readonlySchema' => false,
       'readonly' => false,
    )),
  ),
);
