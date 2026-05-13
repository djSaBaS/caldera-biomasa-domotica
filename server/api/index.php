<?php

// Comentario: Declarar tipos estrictos para reducir errores silenciosos.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir solo consulta de estado mediante GET.
Request::requireMethod(['GET']);

// Comentario: Comprobar disponibilidad de MySQL sin romper la API.
$databaseAvailable = Database::tryConnection() instanceof PDO;

// Comentario: Responder estado básico del backend y endpoints disponibles.
JsonResponse::success(
    [
        'estado' => 'ok',
        'proyecto' => 'caldera-biomasa-domotica',
        'version' => '0.3.0-sprint-02-persistencia-auth',
        'modo' => 'desarrollo-seguro',
        'database_available' => $databaseAvailable,
        'endpoints' => [
            '/api/auth_login.php',
            '/api/auth_me.php',
            '/api/auth_logout.php',
            '/api/password_reset_request.php',
            '/api/telemetry.php',
            '/api/config.php',
            '/api/command.php',
            '/api/config_ack.php',
            '/api/events.php',
            '/api/fuel.php',
            '/api/maintenance.php',
        ],
    ]
);
