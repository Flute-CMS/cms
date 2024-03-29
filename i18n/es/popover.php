<?php

return [
    'admin_settings' => [
        'title' => [
            'admin_header' => 'Configuración del sistema',
            'system' => 'Sistema',
            'authorization' => 'Autorización',
            'databases' => 'Bases de datos',
            'what_is_this' => '¿Qué es esto?',
            'default_db' => 'BD por defecto',
            'debug' => 'Depuración',
            'connections_dbs' => 'Conexiones y BD',
            'connections' => 'Conexiones',
            'in_short' => 'En resumen',
            'dbs' => 'BD',
            'language' => 'Idioma',
            'mail_server' => 'Servidor de correo',
            'profile' => 'Perfil',
            'replenishment' => 'Recarga',
            'summing_up' => 'Sumando...',
        ],
        'description' => [
            'system_settings_intro' => 'Vamos a ver qué son estas configuraciones del sistema y para qué sirven.',
            'system_settings_details' => 'En este apartado, puedes cambiar las configuraciones básicas del motor. Cambiar elementos críticos tiene un impacto significativo, así que hazlo solo si estás seguro.',
            'authorization_settings' => 'Aquí puedes cambiar las configuraciones de autorización (tiempo de sesión, etc.). Supongo que esto es autoexplicativo.',
            'databases_overview' => 'Ahora vamos a ver más de cerca las bases de datos.',
            'database_principles' => 'El principio de las bases de datos en el motor es un poco diferente, así que profundicemos.',
            'default_db_usage' => 'Este apartado controla la base de datos que utilizará Flute. Idealmente, nunca deberías tocar esto.',
            'debug_mode_info' => 'Este apartado activa el modo de depuración en la BD. Se recomienda usarlo solo para desarrolladores.',
            'multiple_connections_dbs' => 'Flute utiliza un sistema de múltiples conexiones y múltiples bases de datos.',
            'connections_info' => 'Las conexiones son los datos para conectarse a la BD (nombre de usuario, contraseña, etc.), mientras que las BD son los datos para los módulos y el motor.',
            'connections_dbs_summary' => 'Puedes tener una sola conexión (si todo está en una sola BD). Pero puedes tener varias BD. En fin, lo entenderás más adelante.',
            'managing_connections' => 'Este apartado te permite manejar todas las conexiones en Flute.',
            'setting_up_dbs' => 'Aquí es donde se configuran y establecen las BD que utilizan las conexiones. ¡Respira hondo y sigamos!',
            'language_settings' => 'Aquí puedes seleccionar el idioma predeterminado del motor y la caché de las traducciones.',
            'mail_server_settings' => 'Aquí configurarás el servidor de correo para enviar correos electrónicos (para restablecer contraseñas, etc.).',
            'profile_settings' => 'Aquí puedes configurar diferentes parámetros para los perfiles de usuario. Cuando entres, lo entenderás todo.',
            'balance_replenishment_settings' => 'Este apartado almacena las configuraciones para la recarga de saldo. Ya sea la moneda mostrada o el monto mínimo de recarga.',
            'tour_ending' => 'No lo creerás, ¡pero hemos terminado nuestro recorrido por los elementos principales! Sé que es mucha información, pero confío en que puedas manejarlo.'
        ]
    ],
    'admin_stats' => [
        'title' => [
            'sidebar' => 'Barra lateral',
            'main_menu' => 'Menú principal',
            'additional_menu' => 'Menú adicional',
            'recent_menu' => 'Visitados recientemente',
            'sidebar_complete' => 'Barra lateral completa',
            'navbar' => 'Barra de navegación',
            'search' => 'Búsqueda',
            'version' => 'Versión',
            'report_generation' => 'Generación de informes',
            'final' => '¡Y eso es todo!',
        ],
        'description' => [
            'sidebar' => 'Vamos a analizar el panel de navegación principal del panel de administración. ¡Empecemos!',
            'main_menu' => 'En este menú se encuentran los elementos relacionados con configuraciones críticas del sistema.',
            'additional_menu' => 'En este menú se encuentran elementos de módulos y componentes del sistema. Entenderás cada elemento a medida que los uses.',
            'recent_menu' => 'En el panel de administración, en la parte inferior, también se muestran las páginas que has visitado recientemente. Puede ser útil si visitas una página con frecuencia y no quieres buscarla cada vez.',
            'sidebar_complete' => 'Terminamos con la barra lateral, ahora pasemos a los otros componentes.',
            'navbar' => 'Analizaremos la barra superior y qué elementos contiene.',
            'search' => 'Este campo te permite encontrar elementos o páginas necesarias utilizando palabras clave. ¡Empieza a escribir y la búsqueda te mostrará los resultados deseados!',
            'version' => 'Aquí se muestra la versión del motor instalado. En el futuro, podrás actualizar el motor directamente desde el panel de administración.',
            'report_generation' => 'Este botón te permite generar un informe detallado sobre el sistema. Si necesitas enviar información sobre un error del sistema, este archivo debe adjuntarse al mensaje.',
            'final' => 'Te he presentado los componentes principales dentro del panel de administración. Hay más consejos en otras páginas. ¡Buena suerte usándolos!'
        ]
    ],
    'home' => [
        'title' => [
            'editor_mode_title' => 'Modo de edición',
            'editor_title' => 'Título de la página en edición',
            'editor_area' => 'Área de edición',
            'editor_toolbar' => 'Herramientas de edición',
            'save_button' => 'Guardar',
            'editor_course_completed' => 'Curso de edición completado',
        ],
        'description' => [
            'editor_mode' => 'El CMS tiene un modo de edición para cada página, lo que permite una personalización completa.',
            'editor_title' => 'Aquí se muestra el título de la página que se va a editar.',
            'editor_area' => 'Aquí puedes crear bloques, widgets y texto que se mostrarán en el editor.',
            'editor_toolbar' => 'Aquí puedes agregar o editar bloques existentes.',
            'save_button' => 'Después de cambiar los datos, asegúrate de guardar todo el contenido haciendo clic en este botón.',
            'editor_course_completed' => 'Esto es todo lo que necesitas saber para tener una comprensión básica del editor. Para obtener más detalles, consulta la documentación oficial.',
        ],
    ]
];