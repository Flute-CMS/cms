<?php

return [
    "back" => "Retour",
    "next" => "Suivant",
    "last_step_required" => "Pour continuer, vous devez compléter la dernière étape !",
    "finish" => "Terminer l'installation !",
    1 => [
        'card_head' => 'Sélection de la langue',
        "title" => "Flute :: Sélection de la langue",
        'Несуществующий язык' => 'Il semble que vous avez sélectionné une langue mystérieuse :0'
    ],
    2 => [
        "title" => "Flute :: Vérification des exigences",
        'card_head' => "Compatibilité",
        'card_head_desc' => "Sur cette page, vous devez vérifier la conformité de toutes les exigences, et si tout est bon, alors procédez à l'installation",
        'req_not_completed' => "Exigences non satisfaites",
        'need_to_install' => "Besoin d'installer",
        'may_installed' => "Recommandé à installer",
        'installed' => "Installé",
        'all_good' => "Tout va bien !",
        'may_unstable' => "Peut fonctionner de manière instable",
        'min_php_7' => "La version minimale de PHP est 7.4 !",
        'php_exts' => "Extensions PHP",
        'other' => 'Autre'
    ],
    3 => [
        "title" => "Flute :: Entrée de la base de données",
        'card_head' => "Connexion à la base de données",
        'card_head_desc' => "Remplissez tous les champs avec les données de votre base de données. Il est préférable de créer une nouvelle base de données.",
        "driver" => "Sélectionnez le pilote de base de données",
        "ip" => "Entrez l'hôte de la base de données",
        "port" => "Entrez le port de la base de données",
        "db" => "Entrez le nom de la base de données",
        "user" => "Entrez l'utilisateur de la base de données",
        "pass" => "Entrez le mot de passe de la base de données",
        'db_error' => "Une erreur s'est produite lors de la connexion : <br>%error%",
        'data_invalid' => "Les données saisies ne sont pas valides !",
        "check_data" => "Vérifier les données",
        "data_correct" => 'Données correctes'
    ],
    4 => [
        "title" => "Flute :: Migration des données",
        'card_head' => "Migration des données",
        'card_head_desc' => "Doit-on migrer les données à partir d'autres CMS ? Sélectionnez le CMS requis (si nécessaire)",
        'migrate_from' => 'Migrer les données à partir de',
        'thanks_but_no' => 'Merci, mais non',
        'card_head_2' => 'Migration des données depuis %cms%',
        'card_desc_2' => 'Sélectionnez les types de migration requis et remplissez les données dans le formulaire',
        'migrate' => [
            'all' => 'Migrer tout',
            'servers' => 'Migrer les serveurs',
            'admins' => 'Migrer les administrateurs',
            'gateways' => 'Migrer les passerelles de paiement',
            'payments' => 'Migrer l\'historique des paiements',
        ]
    ],
    5 => [
        "title" => "Flute :: Inscription du propriétaire",
        'card_head' => "Inscription du propriétaire",
        'card_head_desc' => "Remplissez tous les champs avec les données pour créer votre compte.",
        'login' => 'Connexion',
        'login_placeholder' => 'Entrez votre identifiant',
        'name' => 'Pseudo',
        'name_placeholder' => 'Entrez le nom d\'affichage',
        'email' => 'E-mail',
        'email_placeholder' => 'Entrez l\'e-mail',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Entrez le mot de passe',
        'repassword' => 'Retapez le mot de passe',
        'repassword_placeholder' => 'Entrez à nouveau le mot de passe',
        'login_length' => 'La longueur minimale de connexion est de 2 lettres !',
        'name_length' => 'La longueur minimale du pseudo est de 2 lettres !',
        'pass_length' => 'La longueur minimale du mot de passe est de 4 caractères !',
        'invalid_email' => 'Entrez l\'e-mail correctement !',
        'pass_diff' => 'Les mots de passe saisis ne correspondent pas !',
        'error_create_user' => 'Erreur lors de la création de l\'utilisateur !',
    ],
    6 => [
        "title" => "Flute :: Les infobulles sont-elles activées ?",
        'card_head' => "Activation des infobulles",
        'card_head_desc' => "Avez-vous besoin d'infobulles dans le moteur pour comprendre comment utiliser certaines fonctionnalités ?",
        'yes' => 'Oui, activez-les, je suis ici pour la première fois (recommandé) 🤯',
        'no' => 'Non, j\'ai tourné cette Flute partout 😎'
    ],
    7 => [
        "title" => "Flute :: Rapport d'erreur",
        'card_head' => "Activation du rapport d'erreur",
        'card_head_desc' => "En cas de dysfonctionnement du moteur, les erreurs seront envoyées à notre serveur pour traitement. Après un certain temps, une mise à jour avec une correction peut être publiée grâce à vous 🥰",
        'yes' => 'Oui, envoyez les erreurs pour améliorer les performances du moteur 😇',
        'no' => 'Non, ne rien envoyer, cela ne m\'intéresse pas 🤐'
    ],
];