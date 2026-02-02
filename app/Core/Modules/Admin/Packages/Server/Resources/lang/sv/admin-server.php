<?php

return [
    'search_servers' => 'Sök servrar',
    'title' => [
        'list' => 'Servrar',
        'edit' => 'Redigera server',
        'create' => 'Lägg till server',
        'description' => 'Alla servrar som lagts till i Flute listas här',
        'main_info' => 'Huvudinformation',
        'actions' => 'Åtgärder',
        'actions_description' => 'Åtgärder på servern',
        'integrations' => 'Integrationer',
    ],
    'tabs' => [
        'main' => 'Huvud',
        'db_connections' => 'DB-integrationer',
    ],
    'fields' => [
        'name' => [
            'label' => 'Namn',
            'placeholder' => 'Ange servernamn',
        ],
        'ip' => [
            'label' => 'IP-adress',
            'placeholder' => '127.0.0.1',
        ],
        'port' => [
            'label' => 'Port',
            'placeholder' => '27015',
        ],
        'mod' => [
            'label' => 'Spel',
            'placeholder' => 'Välj spel',
        ],
        'rcon' => [
            'label' => 'RCON-lösenord',
            'placeholder' => 'Ange RCON-lösenord',
            'help' => 'Lösenord för fjärrserverhantering',
        ],
        'display_ip' => [
            'label' => 'Visa IP',
            'placeholder' => '127.0.0.1:27015',
            'help' => 'IP-adress som visas för användare',
        ],
        'ranks' => [
            'label' => 'Rankpaket',
            'placeholder' => 'Välj rankpaket',
        ],
        'ranks_format' => [
            'label' => 'Rankfilformat',
            'placeholder' => 'Välj rankfilformat',
        ],
        'ranks_premier' => [
            'label' => 'Premier-ranker',
            'placeholder' => 'Ska servern använda premier-ranker',
        ],
        'query_port' => [
            'label' => 'Query-port',
            'placeholder' => 'Valfritt. Om tom används anslutningsporten',
        ],
        'rcon_port' => [
            'label' => 'RCON-port',
            'placeholder' => 'Valfritt. Om tom används anslutningsporten',
        ],
        'enabled' => [
            'label' => 'Aktiverad',
            'help' => 'Ska servern vara synlig i den offentliga listan',
        ],
        'created_at' => 'Skapad',
    ],
    'status' => [
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'online' => 'Server online',
        'offline' => 'Server offline',
        'hostname' => 'Värdnamn',
        'map' => 'Karta',
        'players' => 'Spelare',
        'game' => 'Spel',
    ],
    'db_connection' => [
        'title' => 'DB-integrationer',
        'fields' => [
            'mod' => [
                'label' => 'Integration',
                'placeholder' => 'Välj integration',
                'help' => 'Välj en integration (statistik, bannlysningar, VIP, etc.).',
            ],
            'dbname' => [
                'label' => 'Anslutning',
                'placeholder' => 'Välj anslutning',
                'help' => 'Skapad i Inställningar → Databaser.',
            ],
            'driver' => [
                'label' => 'Drivrutin',
                'placeholder' => 'Välj drivrutin',
                'custom' => 'Anpassad',
            ],
            'additional' => [
                'label' => 'Ytterligare inställningar',
                'placeholder' => 'Ange ytterligare inställningar',
            ],
            'params' => 'Param.',
            'custom_driver_name' => [
                'label' => 'Drivrutinsnamn',
                'placeholder' => 'Ange drivrutinsnamn',
            ],
            'json_settings' => [
                'label' => 'JSON-inställningar',
                'placeholder' => 'Ange inställningar i JSON',
                'help' => 'Ange godtyckliga JSON-inställningar',
            ],
        ],
        'add' => [
            'title' => 'Lägg till DB-integration',
            'button' => 'Lägg till integration',
        ],
        'edit' => [
            'title' => 'Redigera DB-integration',
        ],
        'create_db' => [
            'title' => 'Inga databasanslutningar',
            'description' => 'Skapa först en anslutning för att länka en integration.',
            'note' => 'Anslutningen kommer att vara tillgänglig efter sparande.',
            'button' => 'Skapa anslutning',
        ],
        'delete' => [
            'confirm' => 'Är du säker på att du vill ta bort denna anslutning?',
        ],
    ],
    'db_drivers' => [
        'default' => [
            'name' => 'Standard',
            'fields' => [
                'connection' => [
                    'label' => 'Anslutning',
                    'placeholder' => 'Välj DB-anslutning',
                    'help' => 'Välj en databasanslutning från din konfiguration',
                ],
                'table_prefix' => [
                    'label' => 'Tabellprefix',
                    'placeholder' => 'Ange tabellprefix',
                    'help' => 'Prefix för databastabeller',
                ],
            ],
        ],
        'statistics' => [
            'name' => 'Statistik',
            'fields' => [
                'connection' => [
                    'label' => 'Anslutning',
                    'placeholder' => 'Välj DB-anslutning',
                    'help' => 'Välj en databasanslutning från din konfiguration',
                ],
                'table_prefix' => [
                    'label' => 'Tabellprefix',
                    'placeholder' => 'Ange tabellprefix',
                    'help' => 'Prefix för databastabeller',
                ],
                'player_table' => [
                    'label' => 'Spelartabell',
                    'placeholder' => 'Ange spelartabellens namn',
                    'help' => 'Tabell som innehåller spelardata',
                ],
                'steam_id_field' => [
                    'label' => 'Steam ID-fält',
                    'placeholder' => 'Ange Steam ID-fältets namn',
                    'help' => 'Fält som innehåller Steam ID',
                ],
                'name_field' => [
                    'label' => 'Namnfält',
                    'placeholder' => 'Ange namnfältets namn',
                    'help' => 'Fält som innehåller spelarnamnet',
                ],
            ],
        ],
        'no_drivers' => [
            'title' => 'Inga DB-drivrutiner tillgängliga',
            'description' => 'Inga registrerade databasdrivrutiner hittades. Kontakta administratören.',
        ],
    ],
    'mods' => [
        'custom_settings_name' => [
            'title' => 'Drivrutinsnamn',
            'placeholder' => 'Ange drivrutinsnamn',
        ],
        'custom_settings_json' => [
            'title' => 'Inställningar JSON',
            'placeholder' => 'Ange JSON-inställningar',
        ],
        'custom_alert' => [
            'title' => 'Varning!',
            'description' => 'Att ange anpassade inställningar kräver försiktighet! Om du är osäker, lägg inte till anpassade inställningar!',
        ],
        'custom' => 'Anpassad',
    ],
    'buttons' => [
        'add' => 'Lägg till',
        'save' => 'Spara',
        'cancel' => 'Avbryt',
        'delete' => 'Ta bort',
        'edit' => 'Redigera',
        'actions' => 'Åtgärder',
        'test_connection' => 'Testa anslutning',
    ],
    'messages' => [
        'server_not_found' => 'Server hittades inte.',
        'connection_not_found' => 'Anslutning hittades inte.',
        'save_success' => 'Server sparad framgångsrikt.',
        'delete_success' => 'Server borttagen framgångsrikt.',
        'connection_add_success' => 'Anslutning tillagd framgångsrikt.',
        'connection_update_success' => 'Anslutning uppdaterad framgångsrikt.',
        'connection_delete_success' => 'Anslutning borttagen framgångsrikt.',
        'save_server_first' => 'Spara servern först.',
        'invalid_driver_settings' => 'Ogiltiga drivrutinsinställningar.',
        'no_permission.manage' => 'Du har inte behörighet att hantera servrar.',
        'no_permission.delete' => 'Du har inte behörighet att ta bort servrar.',
        'invalid_json' => 'Ogiltigt JSON-format.',
        'server_deleted' => 'Server borttagen framgångsrikt.',
        'server_updated' => 'Server uppdaterad framgångsrikt.',
        'server_created' => 'Server skapad framgångsrikt.',
        'save_not_for_db_connections' => 'Sparande gäller endast huvudserverinfo.',
        'invalid_ip' => 'Ange en giltig IP-adress utan port.',
        'connection_success' => 'Lyckades ansluta till servern.',
        'connection_failed' => 'Misslyckades ansluta till servern',
        'connection_no_response' => 'Servern svarar inte på frågor.',
    ],
    'confirms' => [
        'delete_server' => 'Är du säker på att du vill ta bort denna server? Denna åtgärd kan inte ångras.',
    ],
];
