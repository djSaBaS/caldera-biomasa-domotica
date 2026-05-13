<?php

// Comentario: Declarar tipos estrictos para confirmaciones fiables.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Aceptar únicamente ACK por POST.
Request::requireMethod(['POST']);

// Comentario: Exigir API key de dispositivo.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Leer JSON enviado por ESP32 o Arduino a través del puente.
$payload = Request::jsonBody();

// Comentario: Validar identificador de dispositivo.
$deviceId = trim((string) ($payload['device_id'] ?? ''));

// Comentario: Validar resultado informado por firmware.
$status = trim((string) ($payload['status'] ?? ''));

// Comentario: Rechazar ACK incompleto.
if ($deviceId === '' || !in_array($status, ['aplicada', 'rechazada'], true)) {
    JsonResponse::error('ack_invalido', 'El ACK debe incluir device_id y status aplicada/rechazada.', 422);
}

// Comentario: Confirmar recepción del ACK sin asumir aplicación real.
JsonResponse::success(
    [
        'device_id' => $deviceId,
        'status' => $status,
        'message' => 'ACK de configuración recibido para trazabilidad futura.',
    ],
    [
        'simulation' => true,
    ],
    202
);
