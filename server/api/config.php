<?php

// Comentario: Declarar tipos estrictos para mayor seguridad.
declare(strict_types=1);

// Comentario: Cargar núcleo común y catálogo de configuración.
require_once __DIR__ . '/../app/bootstrap.php';
// Comentario: Cargar validador/catálogo de parámetros de caldera.
require_once __DIR__ . '/../app/Services/BoilerConfigValidator.php';

// Comentario: Permitir únicamente lectura de configuración por GET.
Request::requireMethod(['GET']);

// Comentario: Exigir API key para evitar entrega de parámetros a clientes no autorizados.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Obtener identificador del dispositivo desde query string.
$deviceId = Request::queryString('device_id');

// Comentario: Validar presencia de identificador de dispositivo.
if ($deviceId === '') {
    JsonResponse::error('device_id_requerido', 'El parámetro device_id es obligatorio.', 422);
}

// Comentario: Preparar configuración segura de desarrollo con sinfín ON igual a OFF.
$config = [
    'config_version' => 1,
    'device_id' => $deviceId,
    'mode' => 'manual',
    'auger_cycle_seconds' => 10,
    'fan_primary_pct' => 50,
    'fan_secondary_pct' => 50,
    'pump_on_temp' => 60,
    'target_temp' => 75,
    'maintenance_temp' => 80,
    'safety_temp' => 90,
    'startup_timeout_seconds' => 900,
    'post_ventilation_seconds' => 180,
    'telemetry_interval_seconds' => 10,
    'config_poll_interval_seconds' => 30,
    'notifications_enabled' => 1,
];

// Comentario: Responder configuración junto al catálogo validable.
JsonResponse::success(
    [
        'config' => $config,
        'catalog' => BoilerConfigValidator::catalog(),
        'safety_note' => 'El firmware vuelve a validar límites y puede rechazar cualquier parámetro inseguro.',
    ],
    [
        'simulation' => true,
    ]
);
