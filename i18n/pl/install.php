<?php

return [
    "back" => "Wstecz",
    "next" => "Dalej",
    "last_step_required" => "Aby kontynuować, musisz ukończyć ostatni krok!",
    "finish" => "Zakończ instalację!",
    "1" => [
        'card_head' => 'Wybór języka',
        "title" => "Flute :: Wybór języka",
        'Несуществующий язык' => 'Wygląda na to, że wybrałeś jakiś tajemniczy język :0'
    ],
    2 => [
        "title" => "Flute :: Sprawdzenie wymagań",
        'card_head' => "Zgodność",
        'card_head_desc' => "Na tej stronie musisz sprawdzić zgodność wszystkich wymagań, a jeśli wszystko jest w porządku, przejdź do instalacji",
        'req_not_completed' => "Wymagania nie spełnione",
        'need_to_install' => "Trzeba zainstalować",
        'may_installed' => "Zalecane do zainstalowania",
        'installed' => "Zainstalowane",
        'all_good' => "Wszystko w porządku!",
        'may_unstable' => "Może działać niestabilnie",
        'min_php_7' => "Minimalna wersja PHP to 7.4!",
        'php_exts' => "Rozszerzenia PHP",
        'other' => 'Inne'
    ],
    3 => [
        "title" => "Flute :: Dane bazy danych",
        'card_head' => "Połączenie z bazą danych",
        'card_head_desc' => "Wypełnij wszystkie pola danymi z bazy danych. Preferowane jest utworzenie nowej bazy danych.",
        "driver" => "Wybierz sterownik bazy danych",
        "ip" => "Wprowadź host bazy danych",
        "port" => "Wprowadź port bazy danych",
        "db" => "Wprowadź nazwę bazy danych",
        "user" => "Wprowadź użytkownika bazy danych",
        "pass" => "Wprowadź hasło do bazy danych",
        'db_error' => "Wystąpił błąd podczas połączenia: <br>%error%",
        'data_invalid' => "Wprowadzone dane są nieprawidłowe!",
        "check_data" => "Sprawdź dane",
        "data_correct" => 'Dane poprawne'
    ],
    4 => [
        "title" => "Flute :: Migracja danych",
        'card_head' => "Migracja danych",
        'card_head_desc' => "Czy potrzebujesz przenieść dane z innych CMS-ów? Wybierz wymagane CMS-y (jeśli jest to konieczne)",
        'migrate_from' => 'Przenieś dane z',
        'thanks_but_no' => 'Dzięki, ale nie',
        'card_head_2' => 'Migracja danych z %cms%',
        'card_desc_2' => 'Wybierz wymagane rodzaje migracji i wypełnij dane w formularzu',
        'migrate' => [
            'all' => 'Przenieś wszystko',
            'servers' => 'Przenieś serwery',
            'admins' => 'Przenieś administratorów',
            'gateways' => 'Przenieś bramy płatności',
            'payments' => 'Przenieś historię płatności',
        ]
    ],
    5 => [
        "title" => "Flute :: Rejestracja właściciela",
        'card_head' => "Rejestracja właściciela",
        'card_head_desc' => "Wypełnij wszystkie pola danymi, aby utworzyć swoje konto.",
        'login' => 'Login',
        'login_placeholder' => 'Wprowadź login',
        'name' => 'Nick',
        'name_placeholder' => 'Wprowadź nazwę wyświetlaną',
        'email' => 'Email',
        'email_placeholder' => 'Wprowadź email',
        'password' => 'Hasło',
        'password_placeholder' => 'Wprowadź hasło',
        'repassword' => 'Powtórz hasło',
        'repassword_placeholder' => 'Wprowadź hasło ponownie',
        'login_length' => 'Minimalna długość loginu to 2 litery!',
        'name_length' => 'Minimalna długość nicku to 2 litery!',
        'pass_length' => 'Minimalna długość hasła to 4 znaki!',
        'invalid_email' => 'Wprowadź poprawny email!',
        'pass_diff' => 'Wprowadzone hasła nie pasują do siebie!',
        'error_create_user' => 'Błąd podczas tworzenia użytkownika!',
    ],
    6 => [
        "title" => "Flute :: Czy są włączone podpowiedzi?",
        'card_head' => "Włączanie podpowiedzi",
        'card_head_desc' => "Czy potrzebujesz podpowiedzi w silniku, aby zrozumieć, jak korzystać z określonej funkcjonalności?",
        'yes' => 'Tak, włącz, jestem tutaj po raz pierwszy (zalecane) 🤯',
        'no' => 'Nie, kręcę tym Flutem wszędzie 😎'
    ],
    7 => [
        "title" => "Flute :: Raportowanie błędów",
        'card_head' => "Włączanie raportowania błędów",
        'card_head_desc' => "W przypadku nieprawidłowego działania silnika błędy zostaną wysłane na nasz serwer w celu ich przetworzenia. Po pewnym czasie może zostać wydana aktualizacja z poprawką dzięki Tobie 🥰",
        'yes' => 'Tak, wysyłaj błędy w celu poprawy wydajności silnika 😇',
        'no' => 'Nie, nie wysyłaj niczego, nie interesuje mnie to 🤐'
    ],
];
