<?php

// Comentario: Declarar tipos estrictos para mejorar robustez.
declare(strict_types=1);

// Comentario: Devolver configuración base leída desde variables de entorno.
return [
    // Comentario: Agrupar configuración general de aplicación.
    'app' => [
        // Comentario: Nombre público de la aplicación.
        'name' => getenv('APP_NAME') ?: 'Caldera Biomasa Domótica',
        // Comentario: Entorno actual de ejecución.
        'env' => getenv('APP_ENV') ?: 'development',
        // Comentario: Indicador de depuración para desarrollo local.
        'debug' => (getenv('APP_DEBUG') ?: 'true') === 'true',
    ],
    // Comentario: Agrupar configuración de base de datos sin credenciales reales fijas.
    'db' => [
        // Comentario: Host MySQL configurado fuera de Git.
        'host' => getenv('DB_HOST') ?: 'localhost',
        // Comentario: Puerto MySQL configurado fuera de Git.
        'port' => getenv('DB_PORT') ?: '3306',
        // Comentario: Nombre de base de datos de desarrollo.
        'name' => getenv('DB_NAME') ?: 'caldera_biomasa',
        // Comentario: Usuario de base de datos no productivo por defecto.
        'user' => getenv('DB_USER') ?: 'usuario_desarrollo',
        // Comentario: Contraseña obtenida desde entorno sin incluir secretos reales.
        'pass' => getenv('DB_PASS') ?: '',
        // Comentario: Charset recomendado para español y compatibilidad completa Unicode.
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    // Comentario: Agrupar configuración de seguridad sin secretos reales versionados.
    'security' => [
        // Comentario: Clave API de dispositivo obtenida solo desde entorno configurado.
        'device_api_key' => getenv('DEVICE_API_KEY') ?: '',
    ],
];
