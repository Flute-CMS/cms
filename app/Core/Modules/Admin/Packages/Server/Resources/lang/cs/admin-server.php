<?php

return [
    'search_servers' => 'Hledat servery',
    'title' => [
        'list' => 'Servery',
        'edit' => 'Upravit server',
        'create' => 'Přidat server',
        'description' => 'Zde jsou uvedeny všechny servery přidané do Flute',
        'main_info' => 'Hlavní informace',
        'actions' => 'Akce',
        'actions_description' => 'Akce na serveru',
        'integrations' => 'Integrace',
    ],
    'tabs' => [
        'main' => 'Hlavní',
        'db_connections' => 'DB integrace',
    ],
    'fields' => [
        'name' => [
            'label' => 'Název',
            'placeholder' => 'Zadejte název serveru',
        ],
        'ip' => [
            'label' => 'IP adresa',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label' => 'Port',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label' => 'Hra',
            'placeholder' => 'Vyberte hru',
        ],
        'rcon' => [
            'label' => 'RCON heslo',
            'placeholder' => 'Zadejte RCON heslo',
            'help' => 'Heslo pro vzdálenou správu serveru',
        ],
        'display_ip' => [
            'label' => 'Zobrazená IP',
            'placeholder' => '127.0.0.1:27015',
            'help' => 'IP adresa zobrazená uživatelům',
        ],
        'ranks' => [
            'label' => 'Balíček hodností',
            'placeholder' => 'Vyberte balíček hodností',
        ],
        'ranks_format' => [
            'label' => 'Formát souboru hodností',
            'placeholder' => 'Vyberte formát souboru hodností',
        ],
        'ranks_premier' => [
            'label' => 'Premier hodnosti',
            'placeholder' => 'Má server používat premier hodnosti',
        ],
        'query_port' => [
            'label' => 'Query port',
            'placeholder' => 'Volitelné. Pokud je prázdné, použije se port připojení',
        ],
        'rcon_port' => [
            'label' => 'RCON port',
            'placeholder' => 'Volitelné. Pokud je prázdné, použije se port připojení',
        ],
        'lat' => [
            'label' => 'Zeměpisná šířka',
            'help' => 'Zeměpisná šířka serveru pro výpočet pingu',
        ],
        'lon' => [
            'label' => 'Zeměpisná délka',
            'help' => 'Zeměpisná délka serveru pro výpočet pingu',
        ],
        'enabled' => [
            'label' => 'Povolen',
            'help' => 'Má být server viditelný ve veřejném seznamu',
        ],
        'created_at' => 'Vytvořeno',
    ],
    'status' => [
        'active' => 'Aktivní',
        'inactive' => 'Neaktivní',
        'online' => 'Server online',
        'offline' => 'Server offline',
        'hostname' => 'Název hostitele',
        'map' => 'Mapa',
        'players' => 'Hráči',
        'game' => 'Hra',
        'status' => 'Status',
        'and_more' => '+:count dalších',
    ],
    'db_connection' => [
        'title' => 'DB integrace',
        'fields' => [
            'mod' => [
                'label' => 'Integrace',
                'placeholder' => 'Vyberte integraci',
                'help' => 'Vyberte integraci (statistiky, bany, VIP atd.).',
            ],
            'dbname' => [
                'label' => 'Připojení',
                'placeholder' => 'Vyberte připojení',
                'help' => 'Vytvořeno v Nastavení → Databáze.',
            ],
            'driver' => [
                'label' => 'Ovladač',
                'placeholder' => 'Vyberte ovladač',
                'custom' => 'Vlastní',
            ],
            'additional' => [
                'label' => 'Dodatečná nastavení',
                'placeholder' => 'Zadejte dodatečná nastavení',
            ],
            'params' => 'Parametr.',
            'custom_driver_name' => [
                'label' => 'Název ovladače',
                'placeholder' => 'Zadejte název ovladače',
            ],
            'json_settings' => [
                'label' => 'JSON nastavení',
                'placeholder' => 'Zadejte nastavení v JSON',
                'help' => 'Zadejte libovolná JSON nastavení',
            ],
        ],
        'add' => [
            'title' => 'Přidat DB integraci',
            'button' => 'Přidat integraci',
        ],
        'edit' => [
            'title' => 'Upravit DB integraci',
        ],
        'create_db' => [
            'title' => 'Žádná databázová připojení',
            'description' => 'Nejprve vytvořte připojení pro propojení integrace.',
            'note' => 'Připojení bude dostupné po uložení.',
            'button' => 'Vytvořit připojení',
        ],
        'delete' => [
            'confirm' => 'Opravdu chcete smazat toto připojení?',
        ],
    ],
    'db_drivers' => [
        'default' => [
            'name' => 'Výchozí',
            'fields' => [
                'connection' => [
                    'label' => 'Připojení',
                    'placeholder' => 'Vyberte DB připojení',
                    'help' => 'Vyberte databázové připojení z vaší konfigurace',
                ],
                'table_prefix' => [
                    'label' => 'Prefix tabulek',
                    'placeholder' => 'Zadejte prefix tabulek',
                    'help' => 'Prefix pro databázové tabulky',
                ],
            ],
        ],
        'statistics' => [
            'name' => 'Statistiky',
            'fields' => [
                'connection' => [
                    'label' => 'Připojení',
                    'placeholder' => 'Vyberte DB připojení',
                    'help' => 'Vyberte databázové připojení z vaší konfigurace',
                ],
                'table_prefix' => [
                    'label' => 'Prefix tabulek',
                    'placeholder' => 'Zadejte prefix tabulek',
                    'help' => 'Prefix pro databázové tabulky',
                ],
                'player_table' => [
                    'label' => 'Tabulka hráčů',
                    'placeholder' => 'Zadejte název tabulky hráčů',
                    'help' => 'Tabulka obsahující data hráčů',
                ],
                'steam_id_field' => [
                    'label' => 'Pole Steam ID',
                    'placeholder' => 'Zadejte název pole Steam ID',
                    'help' => 'Pole obsahující Steam ID',
                ],
                'name_field' => [
                    'label' => 'Pole jména',
                    'placeholder' => 'Zadejte název pole jména',
                    'help' => 'Pole obsahující jméno hráče',
                ],
            ],
        ],
        'no_drivers' => [
            'title' => 'Žádné DB ovladače k dispozici',
            'description' => 'Nebyly nalezeny žádné registrované databázové ovladače. Kontaktujte správce.',
        ],
    ],
    'mods' => [
        'custom_settings_name' => [
            'title' => 'Název ovladače',
            'placeholder' => 'Zadejte název ovladače',
        ],
        'custom_settings_json' => [
            'title' => 'Nastavení JSON',
            'placeholder' => 'Zadejte JSON nastavení',
        ],
        'custom_alert' => [
            'title' => 'Varování!',
            'description' => 'Zadávání vlastních nastavení vyžaduje opatrnost! Pokud si nejste jisti, nepřidávejte vlastní nastavení!',
        ],
        'custom' => 'Vlastní',
    ],
    'buttons' => [
        'add' => 'Přidat',
        'save' => 'Uložit',
        'cancel' => 'Zrušit',
        'delete' => 'Smazat',
        'edit' => 'Upravit',
        'actions' => 'Akce',
        'test_connection' => 'Testovat připojení',
    ],
    'messages' => [
        'server_not_found' => 'Server nebyl nalezen.',
        'connection_not_found' => 'Připojení nebylo nalezeno.',
        'save_success' => 'Server byl úspěšně uložen.',
        'delete_success' => 'Server byl úspěšně smazán.',
        'connection_add_success' => 'Připojení bylo úspěšně přidáno.',
        'connection_update_success' => 'Připojení bylo úspěšně aktualizováno.',
        'connection_delete_success' => 'Připojení bylo úspěšně smazáno.',
        'save_server_first' => 'Nejprve uložte server.',
        'invalid_driver_settings' => 'Neplatná nastavení ovladače.',
        'no_permission.manage' => 'Nemáte oprávnění ke správě serverů.',
        'no_permission.delete' => 'Nemáte oprávnění ke smazání serverů.',
        'invalid_json' => 'Neplatný JSON formát.',
        'server_deleted' => 'Server byl úspěšně odebrán.',
        'server_updated' => 'Server byl úspěšně aktualizován.',
        'server_created' => 'Server byl úspěšně vytvořen.',
        'save_not_for_db_connections' => 'Ukládání je pouze pro hlavní informace o serveru.',
        'invalid_ip' => 'Zadejte platnou IP adresu bez portu.',
        'connection_success' => 'Úspěšně připojeno k serveru.',
        'connection_failed' => 'Nepodařilo se připojit k serveru',
        'connection_no_response' => 'Server neodpovídá na dotazy.',
    ],
    'confirms' => [
        'delete_server' => 'Opravdu chcete smazat tento server? Tuto akci nelze vrátit zpět.',
    ],
];
