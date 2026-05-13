<?php

// Comentario: Declarar tipos estrictos para reducir errores silenciosos.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir solo consulta de estado mediante GET.
Request::requireMethod(['GET']);

// Comentario: Responder estado básico del backend y endpoints disponibles.
JsonResponse::success(
    [
        'estado' => 'ok',
        'proyecto' => 'caldera-biomasa-domotica',
        'version' => '0.2.0-sprint-01-base',
        'modo' => 'desarrollo-seguro',
        'endpoints' => [
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
