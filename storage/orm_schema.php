<?php return array (
  'apiKey' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\ApiKey',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'api_keys',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'key' => 'key',
      'name' => 'name',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'lastUsedAt' => 'last_used_at',
    ),
    10 => 
    array (
      'permissions' => 
      array (
        0 => 14,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'apiKeyPermission',
          50 => 'apiKey_id',
          51 => 'permission_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'key' => 'string',
      'name' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'lastUsedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'apiKeyPermission' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\ApiKeyPermission',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'api_key_permissions',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'apiKey_id' => 'apiKey_id',
      'permission_id' => 'permission_id',
    ),
    10 => 
    array (
      'apiKey' => 
      array (
        0 => 12,
        1 => 'apiKey',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'apiKey_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'permission' => 
      array (
        0 => 12,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'permission_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'apiKey_id' => 'int',
      'permission_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'bucket' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Bucket',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Flute\\Core\\Database\\Repositories\\BucketRepository',
    5 => 'default',
    6 => 'buckets',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'tokens' => 'tokens',
      'replenishedAt' => 'replenished_at',
      'expiresAt' => 'expires_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'string',
      'tokens' => 'int',
      'replenishedAt' => 'int',
      'expiresAt' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'conditionGroup' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\ConditionGroup',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'condition_groups',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'redirect_id' => 'redirect_id',
    ),
    10 => 
    array (
      'conditions' => 
      array (
        0 => 11,
        1 => 'redirectCondition',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'conditionGroup_id',
          4 => NULL,
        ),
      ),
      'redirect' => 
      array (
        0 => 12,
        1 => 'redirect',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'redirect_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'redirect_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'currency' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Currency',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'currencies',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'code' => 'code',
      'minimum_value' => 'minimum_value',
      'exchange_rate' => 'exchange_rate',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
      'paymentGateways' => 
      array (
        0 => 14,
        1 => 'paymentGateway',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'currencyPaymentGateway',
          50 => 'currency_id',
          51 => 'paymentGateway_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'code' => 'string',
      'minimum_value' => 'float',
      'exchange_rate' => 'float',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'currencyPaymentGateway' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\CurrencyPaymentGateway',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'currency_payment_gateways',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'currency_id' => 'currency_id',
      'paymentGateway_id' => 'paymentGateway_id',
    ),
    10 => 
    array (
      'currency' => 
      array (
        0 => 12,
        1 => 'currency',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'currency_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'paymentGateway' => 
      array (
        0 => 12,
        1 => 'paymentGateway',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'paymentGateway_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'currency_id' => 'int',
      'paymentGateway_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'databaseConnection' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\DatabaseConnection',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'database_connections',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'mod' => 'mod',
      'dbname' => 'dbname',
      'additional' => 'additional',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'server_id' => 'server_id',
    ),
    10 => 
    array (
      'server' => 
      array (
        0 => 12,
        1 => 'server',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'server_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'mod' => 'string',
      'dbname' => 'string',
      'additional' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'server_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'footerItem' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\FooterItem',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'footer_items',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'title' => 'title',
      'icon' => 'icon',
      'url' => 'url',
      'new_tab' => 'new_tab',
      'position' => 'position',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'parent_id' => 'parent_id',
    ),
    10 => 
    array (
      'parent' => 
      array (
        0 => 12,
        1 => 'footerItem',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 
          array (
            0 => 'parent_id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'children' => 
      array (
        0 => 11,
        1 => 'footerItem',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'parent_id',
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'title' => 'string',
      'icon' => 'string',
      'url' => 'string',
      'new_tab' => 'bool',
      'position' => 'int',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'parent_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'footerSocial' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\FooterSocial',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'footer_socials',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'icon' => 'icon',
      'url' => 'url',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'icon' => 'string',
      'url' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'module' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Module',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'modules',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'key' => 'key',
      'name' => 'name',
      'description' => 'description',
      'installedVersion' => 'installed_version',
      'status' => 'status',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'key' => 'string',
      'name' => 'string',
      'description' => 'string',
      'installedVersion' => 'string',
      'status' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'navbarItem' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\NavbarItem',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'navbar_items',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'title' => 'title',
      'description' => 'description',
      'url' => 'url',
      'new_tab' => 'new_tab',
      'icon' => 'icon',
      'position' => 'position',
      'visibleOnlyForGuests' => 'visible_only_for_guests',
      'visibleOnlyForLoggedIn' => 'visible_only_for_logged_in',
      'visibility' => 'visibility',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'parent_id' => 'parent_id',
    ),
    10 => 
    array (
      'parent' => 
      array (
        0 => 12,
        1 => 'navbarItem',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 
          array (
            0 => 'parent_id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'children' => 
      array (
        0 => 11,
        1 => 'navbarItem',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'parent_id',
          ),
          4 => NULL,
        ),
      ),
      'roles' => 
      array (
        0 => 14,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'navbarItemRole',
          50 => 'navbarItem_id',
          51 => 'role_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'title' => 'string',
      'description' => 'string',
      'url' => 'string',
      'new_tab' => 'bool',
      'icon' => 'string',
      'position' => 'int',
      'visibleOnlyForGuests' => 'bool',
      'visibleOnlyForLoggedIn' => 'bool',
      'visibility' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'parent_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'navbarItemRole' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\NavbarItemRole',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'navbar_item_roles',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'navbarItem_id' => 'navbarItem_id',
      'role_id' => 'role_id',
    ),
    10 => 
    array (
      'navbarItem' => 
      array (
        0 => 12,
        1 => 'navbarItem',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'navbarItem_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'role' => 
      array (
        0 => 12,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'role_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'navbarItem_id' => 'int',
      'role_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'notification' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Notification',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'notifications',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'icon' => 'icon',
      'url' => 'url',
      'title' => 'title',
      'content' => 'content',
      'type' => 'type',
      'extra_data' => 'extra_data',
      'viewed' => 'viewed',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'user_id' => 'user_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'icon' => 'string',
      'url' => 'string',
      'title' => 'string',
      'content' => 'string',
      'type' => 'string',
      'extra_data' => 'string',
      'viewed' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'user_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'page' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Page',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'pages',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'route' => 'route',
      'title' => 'title',
      'description' => 'description',
      'keywords' => 'keywords',
      'robots' => 'robots',
      'og_image' => 'og_image',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
      'blocks' => 
      array (
        0 => 11,
        1 => 'pageBlock',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'page_id',
          4 => NULL,
        ),
      ),
      'permissions' => 
      array (
        0 => 14,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'pagePermission',
          50 => 'page_id',
          51 => 'permission_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'route' => 'string',
      'title' => 'string',
      'description' => 'string',
      'keywords' => 'string',
      'robots' => 'string',
      'og_image' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'pageBlock' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PageBlock',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'page_blocks',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'widget' => 'widget',
      'gridstack' => 'gridstack',
      'settings' => 'settings',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'page_id' => 'page_id',
    ),
    10 => 
    array (
      'page' => 
      array (
        0 => 12,
        1 => 'page',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'page_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'permissions' => 
      array (
        0 => 14,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'pageBlockPermission',
          50 => 'pageBlock_id',
          51 => 'permission_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'widget' => 'string',
      'gridstack' => 'string',
      'settings' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'page_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'pageBlockPermission' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PageBlockPermission',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'page_block_permissions',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'pageBlock_id' => 'pageBlock_id',
      'permission_id' => 'permission_id',
    ),
    10 => 
    array (
      'permission' => 
      array (
        0 => 12,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'permission_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'pageBlock_id' => 'int',
      'permission_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'pagePermission' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PagePermission',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'page_permissions',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'page_id' => 'page_id',
      'permission_id' => 'permission_id',
    ),
    10 => 
    array (
      'page' => 
      array (
        0 => 12,
        1 => 'page',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'page_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'permission' => 
      array (
        0 => 12,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'permission_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'page_id' => 'int',
      'permission_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'passwordResetToken' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PasswordResetToken',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'password_reset_tokens',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'token' => 'token',
      'expiry' => 'expiry',
      'user_id' => 'user_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'token' => 'string',
      'expiry' => 'datetime',
      'user_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'paymentGateway' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PaymentGateway',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'payment_gateways',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'image' => 'image',
      'adapter' => 'adapter',
      'enabled' => 'enabled',
      'additional' => 'additional',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'image' => 'string',
      'adapter' => 'string',
      'enabled' => 'bool',
      'additional' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'paymentInvoice' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PaymentInvoice',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'payment_invoices',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'gateway' => 'gateway',
      'transactionId' => 'transaction_id',
      'amount' => 'amount',
      'originalAmount' => 'original_amount',
      'isPaid' => 'is_paid',
      'additional' => 'additional',
      'paidAt' => 'paid_at',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'user_id' => 'user_id',
      'promoCode_id' => 'promoCode_id',
      'currency_id' => 'currency_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'promoCode' => 
      array (
        0 => 12,
        1 => 'promoCode',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'promoCode_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'currency' => 
      array (
        0 => 12,
        1 => 'currency',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'currency_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'gateway' => 'string',
      'transactionId' => 'string',
      'amount' => 'float',
      'originalAmount' => 'float',
      'isPaid' => 'bool',
      'additional' => 'string',
      'paidAt' => 'datetime',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'user_id' => 'int',
      'promoCode_id' => 'int',
      'currency_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'permission' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Permission',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'permissions',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'desc' => 'desc',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'desc' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'promoCode' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PromoCode',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'promo_codes',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'code' => 'code',
      'max_usages' => 'max_usages',
      'max_uses_per_user' => 'max_uses_per_user',
      'type' => 'type',
      'value' => 'value',
      'minimum_amount' => 'minimum_amount',
      'expires_at' => 'expires_at',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
      'usages' => 
      array (
        0 => 11,
        1 => 'promoCodeUsage',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'promoCode_id',
          4 => NULL,
        ),
      ),
      'roles' => 
      array (
        0 => 14,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'promoCodeRole',
          50 => 'promoCode_id',
          51 => 'role_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'code' => 'string',
      'max_usages' => 'int',
      'max_uses_per_user' => 'int',
      'type' => 'string',
      'value' => 'float',
      'minimum_amount' => 'float',
      'expires_at' => 'datetime',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'promoCodeRole' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PromoCodeRole',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'promo_code_roles',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'promoCode_id' => 'promoCode_id',
      'role_id' => 'role_id',
    ),
    10 => 
    array (
      'promoCode' => 
      array (
        0 => 12,
        1 => 'promoCode',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'promoCode_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'role' => 
      array (
        0 => 12,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'role_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'promoCode_id' => 'int',
      'role_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'promoCodeUsage' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\PromoCodeUsage',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'promo_code_usages',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'used_at' => 'used_at',
      'promoCode_id' => 'promoCode_id',
      'user_id' => 'user_id',
      'invoice_id' => 'invoice_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'promoCode' => 
      array (
        0 => 12,
        1 => 'promoCode',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'promoCode_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'invoice' => 
      array (
        0 => 12,
        1 => 'paymentInvoice',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'invoice_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'used_at' => 'datetime',
      'promoCode_id' => 'int',
      'user_id' => 'int',
      'invoice_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'redirect' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Redirect',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'redirects',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'fromUrl' => 'from_url',
      'toUrl' => 'to_url',
    ),
    10 => 
    array (
      'conditionGroups' => 
      array (
        0 => 11,
        1 => 'conditionGroup',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'redirect_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'fromUrl' => 'string',
      'toUrl' => 'string',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'redirectCondition' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\RedirectCondition',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'redirect_conditions',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'type' => 'type',
      'operator' => 'operator',
      'value' => 'value',
      'conditionGroup_id' => 'conditionGroup_id',
    ),
    10 => 
    array (
      'conditionGroup' => 
      array (
        0 => 12,
        1 => 'conditionGroup',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'conditionGroup_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'type' => 'string',
      'operator' => 'string',
      'value' => 'string',
      'conditionGroup_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'rememberToken' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\RememberToken',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'remember_tokens',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'token' => 'token',
      'lastUsedAt' => 'last_used_at',
      'user_id' => 'user_id',
      'userDevice_id' => 'userDevice_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'userDevice' => 
      array (
        0 => 12,
        1 => 'userDevice',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'userDevice_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'token' => 'string',
      'lastUsedAt' => 'datetime',
      'user_id' => 'int',
      'userDevice_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'role' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Role',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'roles',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'icon' => 'icon',
      'color' => 'color',
      'priority' => 'priority',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
      'permissions' => 
      array (
        0 => 14,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'rolePermission',
          50 => 'role_id',
          51 => 'permission_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'icon' => 'string',
      'color' => 'string',
      'priority' => 'int',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'rolePermission' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\RolePermission',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'role_permissions',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'role_id' => 'role_id',
      'permission_id' => 'permission_id',
    ),
    10 => 
    array (
      'role' => 
      array (
        0 => 12,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'role_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'permission' => 
      array (
        0 => 12,
        1 => 'permission',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'permission_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'role_id' => 'int',
      'permission_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'server' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Server',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'servers',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'ip' => 'ip',
      'port' => 'port',
      'mod' => 'mod',
      'rcon' => 'rcon',
      'display_ip' => 'display_ip',
      'ranks' => 'ranks',
      'ranks_premier' => 'ranks_premier',
      'ranks_format' => 'ranks_format',
      'additional' => 'additional',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'enabled' => 'enabled',
    ),
    10 => 
    array (
      'dbconnections' => 
      array (
        0 => 11,
        1 => 'databaseConnection',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'server_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'ip' => 'string',
      'port' => 'int',
      'mod' => 'string',
      'rcon' => 'string',
      'display_ip' => 'string',
      'ranks' => 'string',
      'ranks_premier' => 'bool',
      'ranks_format' => 'string',
      'additional' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'enabled' => 'bool',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'socialNetwork' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\SocialNetwork',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'social_networks',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'key' => 'key',
      'settings' => 'settings',
      'cooldownTime' => 'cooldown_time',
      'allowToRegister' => 'allow_to_register',
      'icon' => 'icon',
      'enabled' => 'enabled',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'key' => 'string',
      'settings' => 'string',
      'cooldownTime' => 'int',
      'allowToRegister' => 'bool',
      'icon' => 'string',
      'enabled' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'theme' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\Theme',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'themes',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'key' => 'key',
      'name' => 'name',
      'version' => 'version',
      'author' => 'author',
      'description' => 'description',
      'status' => 'status',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
      'settings' => 
      array (
        0 => 11,
        1 => 'themeSettings',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'theme_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'key' => 'string',
      'name' => 'string',
      'version' => 'string',
      'author' => 'string',
      'description' => 'string',
      'status' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'themeSettings' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\ThemeSettings',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'theme_settings',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'key' => 'key',
      'name' => 'name',
      'value' => 'value',
      'description' => 'description',
      'theme_id' => 'theme_id',
    ),
    10 => 
    array (
      'theme' => 
      array (
        0 => 12,
        1 => 'theme',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'theme_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'key' => 'string',
      'name' => 'string',
      'value' => 'string',
      'description' => 'string',
      'theme_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'user' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\User',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Flute\\Core\\Database\\Repositories\\UserRepository',
    5 => 'default',
    6 => 'users',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'login' => 'login',
      'uri' => 'uri',
      'name' => 'name',
      'avatar' => 'avatar',
      'banner' => 'banner',
      'email' => 'email',
      'password' => 'password',
      'verified' => 'verified',
      'hidden' => 'hidden',
      'isTemporary' => 'is_temporary',
      'balance' => 'balance',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'last_logged' => 'last_logged',
      'password_updated_at' => 'password_updated_at',
      'deletedAt' => 'deleted_at',
    ),
    10 => 
    array (
      'socialNetworks' => 
      array (
        0 => 11,
        1 => 'userSocialNetwork',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
      'roles' => 
      array (
        0 => 14,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'userRole',
          50 => 'user_id',
          51 => 'role_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
      'rememberTokens' => 
      array (
        0 => 11,
        1 => 'rememberToken',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
      'userDevices' => 
      array (
        0 => 11,
        1 => 'userDevice',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
      'blocksGiven' => 
      array (
        0 => 11,
        1 => 'userBlock',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
      'blocksReceived' => 
      array (
        0 => 11,
        1 => 'userBlock',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
      'actionLogs' => 
      array (
        0 => 11,
        1 => 'userActionLog',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
      'invoices' => 
      array (
        0 => 11,
        1 => 'paymentInvoice',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'user_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'login' => 'string',
      'uri' => 'string',
      'name' => 'string',
      'avatar' => 'string',
      'banner' => 'string',
      'email' => 'string',
      'password' => 'string',
      'verified' => 'bool',
      'hidden' => 'bool',
      'isTemporary' => 'bool',
      'balance' => 'float',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'last_logged' => 'datetime',
      'password_updated_at' => 'datetime',
      'deletedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'userActionLog' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\UserActionLog',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'user_action_logs',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'action' => 'action',
      'message' => 'message',
      'data' => 'data',
      'level' => 'level',
      'createdAt' => 'created_at',
      'user_id' => 'user_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'action' => 'string',
      'message' => 'string',
      'data' => 'string',
      'level' => 'string',
      'createdAt' => 'datetime',
      'user_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
    ),
  ),
  'userBlock' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\UserBlock',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'user_blocks',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'reason' => 'reason',
      'blockedFrom' => 'blocked_from',
      'blockedUntil' => 'blocked_until',
      'isActive' => 'is_active',
      'user_id' => 'user_id',
      'blockedBy_id' => 'blockedBy_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'blockedBy' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'blockedBy_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'reason' => 'string',
      'blockedFrom' => 'datetime',
      'blockedUntil' => 'datetime',
      'isActive' => 'bool',
      'user_id' => 'int',
      'blockedBy_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'userDevice' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\UserDevice',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'user_devices',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'deviceDetails' => 'device_details',
      'ip' => 'ip',
      'user_id' => 'user_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'rememberTokens' => 
      array (
        0 => 11,
        1 => 'rememberToken',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'userDevice_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'deviceDetails' => 'string',
      'ip' => 'string',
      'user_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'userRole' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\UserRole',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'user_roles',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'user_id' => 'user_id',
      'role_id' => 'role_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'role' => 
      array (
        0 => 12,
        1 => 'role',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'role_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'user_id' => 'int',
      'role_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'userSocialNetwork' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\UserSocialNetwork',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'user_social_networks',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'value' => 'value',
      'url' => 'url',
      'name' => 'name',
      'hidden' => 'hidden',
      'linkedAt' => 'linked_at',
      'additional' => 'additional',
      'user_id' => 'user_id',
      'socialNetwork_id' => 'socialNetwork_id',
    ),
    10 => 
    array (
      'socialNetwork' => 
      array (
        0 => 12,
        1 => 'socialNetwork',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'socialNetwork_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'value' => 'string',
      'url' => 'string',
      'name' => 'string',
      'hidden' => 'bool',
      'linkedAt' => 'datetime',
      'additional' => 'string',
      'user_id' => 'int',
      'socialNetwork_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'verificationToken' => 
  array (
    1 => 'Flute\\Core\\Database\\Entities\\VerificationToken',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'verification_tokens',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'token' => 'token',
      'expiresAt' => 'expires_at',
      'user_id' => 'user_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'token' => 'string',
      'expiresAt' => 'datetime',
      'user_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'battlePassManualReward' => 
  array (
    1 => 'Flute\\Modules\\BattlePass\\database\\Entities\\BattlePassManualReward',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'battle_pass_manual_rewards',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'steam_id' => 'steam_id',
      'season_id' => 'season_id',
      'level_id' => 'level_id',
      'reward_details' => 'reward_details',
      'reason' => 'reason',
      'created_at' => 'created_at',
      'user_id' => 'user_id',
      'issued_by_id' => 'issued_by_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'issued_by' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'issued_by_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'steam_id' => 'string',
      'season_id' => 'int',
      'level_id' => 'int',
      'reward_details' => 'string',
      'reason' => 'string',
      'created_at' => 'datetime',
      'user_id' => 'int',
      'issued_by_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'inventoryItem' => 
  array (
    1 => 'Flute\\Modules\\Inventory\\Database\\Entities\\InventoryItem',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'inventory_items',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'image' => 'image',
      'item_class' => 'item_class',
      'item_data' => 'item_data',
      'user_id' => 'user_id',
      'createdAt' => 'created_at',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'image' => 'string',
      'item_class' => 'string',
      'item_data' => 'string',
      'user_id' => 'int',
      'createdAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
    ),
  ),
  'gameMode' => 
  array (
    1 => 'Flute\\Modules\\Modes\\database\\Entities\\GameMode',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'game_modes',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'slug' => 'slug',
      'description' => 'description',
      'image' => 'image',
      'icon' => 'icon',
      'sort_order' => 'sort_order',
      'active' => 'active',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
      'servers' => 
      array (
        0 => 14,
        1 => 'server',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'gameModeServer',
          50 => 'gameMode_id',
          51 => 'server_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'slug' => 'string',
      'description' => 'string',
      'image' => 'string',
      'icon' => 'string',
      'sort_order' => 'int',
      'active' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'gameModeServer' => 
  array (
    1 => 'Flute\\Modules\\Modes\\database\\Entities\\GameModeServer',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'game_mode_servers',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'gameMode_id' => 'gameMode_id',
      'server_id' => 'server_id',
    ),
    10 => 
    array (
      'gameMode' => 
      array (
        0 => 12,
        1 => 'gameMode',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'gameMode_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'server' => 
      array (
        0 => 12,
        1 => 'server',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'server_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'gameMode_id' => 'int',
      'server_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'serverStatus' => 
  array (
    1 => 'Flute\\Modules\\Monitoring\\database\\Entities\\ServerStatus',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'server_statuses',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'online' => 'online',
      'players' => 'players',
      'max_players' => 'max_players',
      'map' => 'map',
      'game' => 'game',
      'players_data' => 'players_data',
      'additional' => 'additional',
      'updated_at' => 'updated_at',
      'server_id' => 'server_id',
    ),
    10 => 
    array (
      'server' => 
      array (
        0 => 12,
        1 => 'server',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'server_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'online' => 'bool',
      'players' => 'int',
      'max_players' => 'int',
      'map' => 'string',
      'game' => 'string',
      'players_data' => 'string',
      'additional' => 'string',
      'updated_at' => 'datetime',
      'server_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'newsArticle' => 
  array (
    1 => 'Flute\\Modules\\News\\database\\Entities\\NewsArticle',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'news_articles',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'title' => 'title',
      'slug' => 'slug',
      'content' => 'content',
      'excerpt' => 'excerpt',
      'featured_image' => 'featured_image',
      'is_published' => 'is_published',
      'is_featured' => 'is_featured',
      'published_at' => 'published_at',
      'views' => 'views',
      'author_id' => 'author_id',
      'category_id' => 'category_id',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'newsCategory_id' => 'newsCategory_id',
    ),
    10 => 
    array (
      'category' => 
      array (
        0 => 12,
        1 => 'newsCategory',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'category_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'author' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'author_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'comments' => 
      array (
        0 => 11,
        1 => 'newsComment',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'newsArticle_id',
          4 => NULL,
        ),
      ),
      'tags' => 
      array (
        0 => 14,
        1 => 'newsTag',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'newsArticleTag',
          50 => 'newsArticle_id',
          51 => 'newsTag_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'title' => 'string',
      'slug' => 'string',
      'content' => 'string',
      'excerpt' => 'string',
      'featured_image' => 'string',
      'is_published' => 'bool',
      'is_featured' => 'bool',
      'published_at' => 'datetime',
      'views' => 'int',
      'author_id' => 'int',
      'category_id' => 'int',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'newsCategory_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'newsArticleTag' => 
  array (
    1 => 'Flute\\Modules\\News\\database\\Entities\\NewsArticleTag',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'news_article_tags',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'article_id' => 'article_id',
      'tag_id' => 'tag_id',
      'newsArticle_id' => 'newsArticle_id',
      'newsTag_id' => 'newsTag_id',
    ),
    10 => 
    array (
      'article' => 
      array (
        0 => 12,
        1 => 'newsArticle',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'article_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'tag' => 
      array (
        0 => 12,
        1 => 'newsTag',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'tag_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'article_id' => 'int',
      'tag_id' => 'int',
      'newsArticle_id' => 'int',
      'newsTag_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'newsCategory' => 
  array (
    1 => 'Flute\\Modules\\News\\database\\Entities\\NewsCategory',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'news_categories',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'slug' => 'slug',
      'description' => 'description',
      'color' => 'color',
      'sort_order' => 'sort_order',
      'is_active' => 'is_active',
      'parent_id' => 'parent_id',
      'newsCategory_id' => 'newsCategory_id',
    ),
    10 => 
    array (
      'parent' => 
      array (
        0 => 12,
        1 => 'newsCategory',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'parent_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'children' => 
      array (
        0 => 11,
        1 => 'newsCategory',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'newsCategory_id',
          4 => NULL,
        ),
      ),
      'articles' => 
      array (
        0 => 11,
        1 => 'newsArticle',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'newsCategory_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'slug' => 'string',
      'description' => 'string',
      'color' => 'string',
      'sort_order' => 'int',
      'is_active' => 'bool',
      'parent_id' => 'int',
      'newsCategory_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'newsComment' => 
  array (
    1 => 'Flute\\Modules\\News\\database\\Entities\\NewsComment',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'news_comments',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'content' => 'content',
      'status' => 'status',
      'article_id' => 'article_id',
      'user_id' => 'user_id',
      'parent_id' => 'parent_id',
      'createdAt' => 'created_at',
      'newsArticle_id' => 'newsArticle_id',
      'newsComment_id' => 'newsComment_id',
    ),
    10 => 
    array (
      'article' => 
      array (
        0 => 12,
        1 => 'newsArticle',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'article_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'parent' => 
      array (
        0 => 12,
        1 => 'newsComment',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'parent_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'replies' => 
      array (
        0 => 11,
        1 => 'newsComment',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'newsComment_id',
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'content' => 'string',
      'status' => 'string',
      'article_id' => 'int',
      'user_id' => 'int',
      'parent_id' => 'int',
      'createdAt' => 'datetime',
      'newsArticle_id' => 'int',
      'newsComment_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
    ),
  ),
  'newsTag' => 
  array (
    1 => 'Flute\\Modules\\News\\database\\Entities\\NewsTag',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'news_tags',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'slug' => 'slug',
    ),
    10 => 
    array (
      'articles' => 
      array (
        0 => 14,
        1 => 'newsArticle',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'newsArticleTag',
          50 => 'newsTag_id',
          51 => 'newsArticle_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'slug' => 'string',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'notificationEvent' => 
  array (
    1 => 'Flute\\Modules\\Notifications\\database\\Entities\\NotificationEvent',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'notification_events',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'event_class' => 'event_class',
      'key' => 'key',
      'name' => 'name',
      'description' => 'description',
      'title' => 'title',
      'content' => 'content',
      'icon' => 'icon',
      'active' => 'active',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
    ),
    10 => 
    array (
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'event_class' => 'string',
      'key' => 'string',
      'name' => 'string',
      'description' => 'string',
      'title' => 'string',
      'content' => 'string',
      'icon' => 'string',
      'active' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'notificationHistory' => 
  array (
    1 => 'Flute\\Modules\\Notifications\\database\\Entities\\NotificationHistory',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'notification_histories',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'type' => 'type',
      'title' => 'title',
      'content' => 'content',
      'icon' => 'icon',
      'event_data' => 'event_data',
      'status' => 'status',
      'createdAt' => 'created_at',
      'event_id' => 'event_id',
      'recipient_id' => 'recipient_id',
    ),
    10 => 
    array (
      'event' => 
      array (
        0 => 12,
        1 => 'notificationEvent',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'event_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'recipient' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 'recipient_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'type' => 'string',
      'title' => 'string',
      'content' => 'string',
      'icon' => 'string',
      'event_data' => 'string',
      'status' => 'string',
      'createdAt' => 'datetime',
      'event_id' => 'int',
      'recipient_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
    ),
  ),
  'ruleCategory' => 
  array (
    1 => 'Flute\\Modules\\Rules\\database\\Entities\\RuleCategory',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'rule_categories',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'slug' => 'slug',
      'content' => 'content',
      'sort_order' => 'sort_order',
      'active' => 'active',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'parent_id' => 'parent_id',
    ),
    10 => 
    array (
      'parent' => 
      array (
        0 => 12,
        1 => 'ruleCategory',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 
          array (
            0 => 'parent_id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'children' => 
      array (
        0 => 11,
        1 => 'ruleCategory',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'parent_id',
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'slug' => 'string',
      'content' => 'string',
      'sort_order' => 'int',
      'active' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'parent_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'category' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\Category',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'categories',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'slug' => 'slug',
      'sort_order' => 'sort_order',
      'image' => 'image',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'parent_id' => 'parent_id',
    ),
    10 => 
    array (
      'products' => 
      array (
        0 => 11,
        1 => 'product',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'category_id',
          4 => NULL,
        ),
      ),
      'discounts' => 
      array (
        0 => 14,
        1 => 'discount',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'discountCategory',
          50 => 'category_id',
          51 => 'discount_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
      'parent' => 
      array (
        0 => 12,
        1 => 'category',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          33 => 
          array (
            0 => 'parent_id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'children' => 
      array (
        0 => 11,
        1 => 'category',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => true,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'parent_id',
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'slug' => 'string',
      'sort_order' => 'int',
      'image' => 'string',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'parent_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'discount' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\Discount',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'discounts',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'discount_percent' => 'discount_percent',
      'active' => 'active',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'start_date' => 'start_date',
      'end_date' => 'end_date',
    ),
    10 => 
    array (
      'products' => 
      array (
        0 => 14,
        1 => 'product',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'discountProduct',
          50 => 'discount_id',
          51 => 'product_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
      'categories' => 
      array (
        0 => 14,
        1 => 'category',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'discountCategory',
          50 => 'discount_id',
          51 => 'category_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'discount_percent' => 'float',
      'active' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'start_date' => 'datetime',
      'end_date' => 'datetime',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'discountCategory' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\DiscountCategory',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'discount_categories',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'category_id' => 'category_id',
      'discount_id' => 'discount_id',
    ),
    10 => 
    array (
      'discount' => 
      array (
        0 => 12,
        1 => 'discount',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'discount_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'category' => 
      array (
        0 => 12,
        1 => 'category',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'category_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'category_id' => 'int',
      'discount_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'discountProduct' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\DiscountProduct',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'discount_products',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'discount_id' => 'discount_id',
      'product_id' => 'product_id',
    ),
    10 => 
    array (
      'discount' => 
      array (
        0 => 12,
        1 => 'discount',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'discount_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'product' => 
      array (
        0 => 12,
        1 => 'product',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'product_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'discount_id' => 'int',
      'product_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'product' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\Product',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'products',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'name' => 'name',
      'slug' => 'slug',
      'driver_type' => 'driver_type',
      'driver_config' => 'driver_config',
      'descriptions' => 'descriptions',
      'times' => 'times',
      'server_assignments' => 'server_assignments',
      'sort_order' => 'sort_order',
      'active' => 'active',
      'server_mode' => 'server_mode',
      'apply_discount_to_forever' => 'apply_discount_to_forever',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'category_id' => 'category_id',
    ),
    10 => 
    array (
      'category' => 
      array (
        0 => 12,
        1 => 'category',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'category_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'images' => 
      array (
        0 => 11,
        1 => 'productImage',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'product_id',
          4 => NULL,
        ),
      ),
      'drivers' => 
      array (
        0 => 11,
        1 => 'productDriver',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 'product_id',
          4 => NULL,
        ),
      ),
      'discounts' => 
      array (
        0 => 14,
        1 => 'discount',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          41 => 
          array (
          ),
          42 => 
          array (
          ),
          33 => 
          array (
            0 => 'id',
          ),
          32 => 
          array (
            0 => 'id',
          ),
          52 => 'discountProduct',
          50 => 'product_id',
          51 => 'discount_id',
          54 => 
          array (
          ),
          4 => NULL,
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'name' => 'string',
      'slug' => 'string',
      'driver_type' => 'string',
      'driver_config' => 'string',
      'descriptions' => 'string',
      'times' => 'string',
      'server_assignments' => 'string',
      'sort_order' => 'int',
      'active' => 'bool',
      'server_mode' => 'int',
      'apply_discount_to_forever' => 'bool',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'category_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
  'productDriver' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\ProductDriver',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'product_drivers',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'driver_type' => 'driver_type',
      'driver_config' => 'driver_config',
      'sort_order' => 'sort_order',
      'product_id' => 'product_id',
    ),
    10 => 
    array (
      'product' => 
      array (
        0 => 12,
        1 => 'product',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'product_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'driver_type' => 'string',
      'driver_config' => 'string',
      'sort_order' => 'int',
      'product_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'productImage' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\ProductImage',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'product_images',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'image_path' => 'image_path',
      'sort_order' => 'sort_order',
      'product_id' => 'product_id',
    ),
    10 => 
    array (
      'product' => 
      array (
        0 => 12,
        1 => 'product',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'product_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'image_path' => 'string',
      'sort_order' => 'int',
      'product_id' => 'int',
    ),
    14 => 
    array (
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
    ),
  ),
  'purchaseHistory' => 
  array (
    1 => 'Flute\\Modules\\Shop\\database\\Entities\\PurchaseHistory',
    2 => 'Cycle\\ORM\\Mapper\\Mapper',
    3 => 'Cycle\\ORM\\Select\\Source',
    4 => 'Cycle\\ORM\\Select\\Repository',
    5 => 'default',
    6 => 'purchase_histories',
    7 => 
    array (
      0 => 'id',
    ),
    8 => 
    array (
      0 => 'id',
    ),
    9 => 
    array (
      'id' => 'id',
      'price' => 'price',
      'discount_amount' => 'discount_amount',
      'original_price' => 'original_price',
      'product_options' => 'product_options',
      'driver_type' => 'driver_type',
      'driver_config' => 'driver_config',
      'server_id' => 'server_id',
      'createdAt' => 'created_at',
      'updatedAt' => 'updated_at',
      'user_id' => 'user_id',
      'product_id' => 'product_id',
    ),
    10 => 
    array (
      'user' => 
      array (
        0 => 12,
        1 => 'user',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'user_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
      'product' => 
      array (
        0 => 12,
        1 => 'product',
        3 => 10,
        2 => 
        array (
          30 => true,
          31 => false,
          33 => 'product_id',
          32 => 
          array (
            0 => 'id',
          ),
        ),
      ),
    ),
    12 => NULL,
    13 => 
    array (
      'id' => 'int',
      'price' => 'float',
      'discount_amount' => 'float',
      'original_price' => 'float',
      'product_options' => 'string',
      'driver_type' => 'string',
      'driver_config' => 'string',
      'server_id' => 'int',
      'createdAt' => 'datetime',
      'updatedAt' => 'datetime',
      'user_id' => 'int',
      'product_id' => 'int',
    ),
    14 => 
    array (
    ),
    18 => 
    array (
      0 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\CreatedAt',
        1 => 
        array (
          'field' => 'createdAt',
        ),
      ),
      1 => 
      array (
        0 => 'Cycle\\ORM\\Entity\\Behavior\\Listener\\UpdatedAt',
        1 => 
        array (
          'field' => 'updatedAt',
          'nullable' => false,
        ),
      ),
    ),
    19 => NULL,
    20 => 
    array (
      'id' => 2,
      'createdAt' => 1,
      'updatedAt' => 5,
    ),
  ),
);