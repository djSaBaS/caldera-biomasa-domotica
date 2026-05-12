<?php

// Comentario: Declarar tipos estrictos para mejorar robustez.
declare(strict_types=1);

// Comentario: Devolver configuración base del proyecto.
// Comentario: No usar credenciales reales en este archivo; crear configuración local fuera de Git.
return [
    'app' => [
        'name' => 'Caldera Biomasa Domótica',
        'env' => 'development',
        'debug' => true,
    ],
    'db' => [
        'host' => 'localhost',
        'name' => 'caldera_biomasa',
        'user' => 'caldera_user',
        'pass' => 'cambiar_esto',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'device_api_key' => 'cambiar_esto',
    ],
];
