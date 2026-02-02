<?php

return [
    'title' => [
        'list' => 'API-nycklar',
        'description' => 'Hantera API-nycklar för extern åtkomst',
        'create' => 'Skapa API-nyckel',
        'edit' => 'Redigera API-nyckel',
    ],
    'fields' => [
        'key' => [
            'label' => 'API-nyckel',
            'placeholder' => 'Ange API-nyckel',
            'help' => 'Denna nyckel kommer att användas för API-autentisering',
        ],
        'name' => [
            'label' => 'Namn',
            'placeholder' => 'Ange nyckelnamn',
            'help' => 'Du kan använda detta namn för att identifiera nyckeln',
        ],
        'permissions' => [
            'label' => 'Behörigheter',
        ],
        'created_at' => 'Skapad',
        'last_used_at' => 'Senast använd',
        'never' => 'Aldrig',
    ],
    'buttons' => [
        'actions' => 'Åtgärder',
        'add' => 'Lägg till nyckel',
        'save' => 'Spara',
        'edit' => 'Redigera',
        'delete' => 'Ta bort',
    ],
    'confirms' => [
        'delete_key' => 'Är du säker på att du vill ta bort denna API-nyckel?',
    ],
    'messages' => [
        'save_success' => 'API-nyckel sparad framgångsrikt.',
        'key_not_found' => 'API-nyckel hittades inte.',
        'no_permissions' => 'Välj minst en behörighet.',
        'update_success' => 'API-nyckel uppdaterad framgångsrikt.',
        'update_error' => 'Fel vid uppdatering av API-nyckel: :message',
        'delete_success' => 'API-nyckel borttagen framgångsrikt.',
        'delete_error' => 'Fel vid borttagning av API-nyckel: :message',
    ],
];
