<?php

return [
    "back" => "Atrás",
    "next" => "Siguiente",
    "last_step_required" => "¡Para continuar, necesitas completar el último paso!",
    "finish" => "¡Terminar Instalación!",
    "1" => [
        'card_head' => 'Selección de Idioma',
        "title" => "Flute :: Selección de Idioma",
        'Несуществующий язык' => 'Parece que seleccionaste un idioma misterioso :0'
    ],
    2 => [
        "title" => "Flute :: Comprobación de Requisitos",
        'card_head' => "Compatibilidad",
        'card_head_desc' => "En esta página, necesitas verificar el cumplimiento de todos los requisitos, y si todo está bien, entonces proceder con la instalación",
        'req_not_completed' => "Requisitos no cumplidos",
        'need_to_install' => "Necesita instalar",
        'may_installed' => "Recomendado instalar",
        'installed' => "Instalado",
        'all_good' => "¡Todo bien!",
        'may_unstable' => "Puede funcionar de manera inestable",
        'min_php_7' => "¡La versión mínima de PHP es 7.4!",
        'php_exts' => "Extensiones de PHP",
        'other' => 'Otro'
    ],
    3 => [
        "title" => "Flute :: Entrada de Base de Datos",
        'card_head' => "Conexión de Base de Datos",
        'card_head_desc' => "Completa todos los campos con los datos de tu base de datos. Es preferible crear una base de datos nueva.",
        "driver" => "Seleccionar Controlador de Base de Datos",
        "ip" => "Ingresar Host de la Base de Datos",
        "port" => "Ingresar Puerto de la Base de Datos",
        "db" => "Ingresar Nombre de la Base de Datos",
        "user" => "Ingresar Usuario de la Base de Datos",
        "pass" => "Ingresar Contraseña de la Base de Datos",
        'db_error' => "Se produjo un error al conectar: <br>%error%",
        'data_invalid' => "¡Los datos ingresados son inválidos!",
        "check_data" => "Verificar Datos",
        "data_correct" => 'Datos Correctos'
    ],
    4 => [
        "title" => "Flute :: Migración de Datos",
        'card_head' => "Migración de Datos",
        'card_head_desc' => "¿Necesitas migrar datos de otro CMS? Selecciona el CMS requerido (si es necesario)",
        'migrate_from' => 'Migrar Datos Desde',
        'thanks_but_no' => 'Gracias, pero no',
        'card_head_2' => 'Migración de Datos desde %cms%',
        'card_desc_2' => 'Selecciona los tipos de migración requeridos y completa los datos en el formulario',
        'migrate' => [
            'all' => 'Migrar Todo',
            'servers' => 'Migrar Servidores',
            'admins' => 'Migrar Administradores',
            'gateways' => 'Migrar Pasarelas de Pago',
            'payments' => 'Migrar Historial de Pagos',
        ]
    ],
    5 => [
        "title" => "Flute :: Registro de Propietario",
        'card_head' => "Registro de Propietario",
        'card_head_desc' => "Completa todos los campos con los datos para crear tu cuenta.",
        'login' => 'Inicio de Sesión',
        'login_placeholder' => 'Ingresar inicio de sesión',
        'name' => 'Apodo',
        'name_placeholder' => 'Ingresar nombre de visualización',
        'email' => 'Correo Electrónico',
        'email_placeholder' => 'Ingresar Correo Electrónico',
        'password' => 'Contraseña',
        'password_placeholder' => 'Ingresar contraseña',
        'repassword' => 'Reingresar contraseña',
        'repassword_placeholder' => 'Ingresar contraseña nuevamente',
        'login_length' => '¡La longitud mínima de inicio de sesión es de 2 letras!',
        'name_length' => '¡La longitud mínima del apodo es de 2 letras!',
        'pass_length' => '¡La longitud mínima de la contraseña es de 4 caracteres!',
        'invalid_email' => '¡Ingresa el correo electrónico correctamente!',
        'pass_diff' => '¡Las contraseñas ingresadas no coinciden!',
        'error_create_user' => '¡Error al crear usuario!',
    ],
    6 => [
        "title" => "Flute :: ¿Están Activados los Consejos?",
        'card_head' => "Activando Consejos",
        'card_head_desc' => "¿Necesitas consejos en el motor para entender cómo usar cierta funcionalidad?",
        'yes' => 'Sí, activar, estoy aquí por primera vez (recomendado) 🤯',
        'no' => 'No, he estado usando esta Flauta en todas partes 😎'
    ],
    7 => [
        "title" => "Flute :: Informe de Errores",
        'card_head' => "Activando Informe de Errores",
        'card_head_desc' => "En caso de mal funcionamiento del motor, los errores se enviarán a nuestro servidor para su procesamiento. Después de algún tiempo, puede lanzarse una actualización con una corrección gracias a ti 🥰",
        'yes' => 'Sí, enviar errores para mejorar el rendimiento del motor 😇',
        'no' => 'No, no enviar nada, no me interesa 🤐'
    ],
];
