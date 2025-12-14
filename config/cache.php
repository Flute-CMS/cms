<?php

return array (
  'driver' => 'file',
  'directory' => path('storage/app/cache'),
  'stale_directory' => path('storage/app/cache_stale'),
  'stale_ttl' => 86400,
);
