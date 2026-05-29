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
$deviceUid = Validation::requiredString($payload, 'device_id', 'El campo device_id es obligatorio.', 80);

// Comentario: Validar resultado informado por firmware.
$status = Validation::allowedString($payload, 'status', ['aplicada', 'rechazada'], 'El ACK debe indicar status aplicada o rechazada.');

// Comentario: Intentar persistir ACK como evento de configuración.
$connection = Database::tryConnection();

// Comentario: Preparar metadatos de persistencia.
$meta = ['persistence' => 'skipped'];

// Comentario: Guardar evento si hay base y dispositivo registrado.
if ($connection instanceof PDO) {
    $deviceId = DeviceRepository::findIdByUid($connection, $deviceUid);

    // Comentario: Insertar evento asociado si el dispositivo existe.
    if ($deviceId !== null) {
        $eventId = EventRepository::store($connection, $deviceId, [
            'event_type' => 'configuracion',
            'severity' => $status === 'aplicada' ? 'info' : 'aviso',
            'origin' => 'firmware',
            'title' => 'ACK de configuración ' . $status,
            'message' => (string) ($payload['message'] ?? ''),
        ]);

        // Comentario: Informar evento persistido.
        $meta = ['persistence' => 'stored', 'event_id' => $eventId];
    }
}

// Comentario: Confirmar recepción del ACK.
JsonResponse::success(
    [
        'device_id' => $deviceUid,
        'status' => $status,
        'message' => 'ACK de configuración recibido.',
    ],
    $meta,
    202
);
