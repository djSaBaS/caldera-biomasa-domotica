<?php

// Comentario: Declarar tipos estrictos para lectura segura de comandos.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir que el dispositivo consulte comandos pendientes.
Request::requireMethod(['GET']);

// Comentario: Exigir API key del dispositivo.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Obtener identificador del dispositivo.
$deviceId = Request::queryString('device_id');

// Comentario: Validar que el dispositivo esté identificado.
if ($deviceId === '') {
    JsonResponse::error('device_id_requerido', 'El parámetro device_id es obligatorio.', 422);
}

// Comentario: Entregar cola vacía en Sprint 01 para evitar acciones remotas reales.
JsonResponse::success(
    [
        'device_id' => $deviceId,
        'commands' => [],
        'message' => 'No hay comandos pendientes; el encendido remoto queda bloqueado en modo base.',
    ],
    [
        'remote_start_enabled' => false,
    ]
);
