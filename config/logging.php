<?php

return array (
  'loggers' => 
  array (
    'flute' => 
    array (
      'path' => path('storage/logs/flute.log'),
      'level' => 100,
    ),
    'modules' => 
    array (
      'path' => path('storage/logs/modules.log'),
      'level' => 200,
    ),
    'templates' => 
    array (
      'path' => path('storage/logs/templates.log'),
      'level' => 200,
    ),
    'database' => 
    array (
      'path' => path('storage/logs/database.log'),
      'level' => 200,
    ),
  ),
);
