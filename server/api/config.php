<?php

// Comentario: Declarar tipos estrictos para mayor seguridad.
declare(strict_types=1);

// Comentario: Responder siempre en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Comentario: Obtener identificador de dispositivo desde query string.
$deviceId = $_GET['device_id'] ?? '';

// Comentario: Validar identificador mínimo del dispositivo.
if ($deviceId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'device_id requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Comentario: Devolver configuración temporal segura para desarrollo.
echo json_encode(
    [
        'config_version' => 1,
        'mode' => 'manual',
        'auger_cycle_seconds' => 10,
        'fan_primary_pct' => 50,
        'fan_secondary_pct' => 50,
        'pump_on_temp' => 60,
        'target_temp' => 75,
        'maintenance_temp' => 80,
        'safety_temp' => 90,
        'telemetry_interval_seconds' => 10,
        'config_poll_interval_seconds' => 30,
    ],
    JSON_UNESCAPED_UNICODE
);
