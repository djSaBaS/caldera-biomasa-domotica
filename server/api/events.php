<?php

// Comentario: Declarar tipos estrictos para eventos del sistema.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir alta de evento en modo API base.
Request::requireMethod(['POST']);

// Comentario: Exigir clave API para eventos originados por dispositivo.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Leer payload JSON validado.
$payload = Request::jsonBody();

// Comentario: Validar severidad contra catálogo permitido.
$severity = Validation::allowedString($payload, 'severity', ['info', 'aviso', 'error', 'critico'], 'La severidad indicada no está permitida.');

// Comentario: Validar tipo de evento contra catálogo permitido.
$eventType = Validation::allowedString($payload, 'event_type', ['estado', 'configuracion', 'orden', 'comunicacion', 'sensor', 'mantenimiento', 'sistema'], 'El tipo de evento no está permitido.');

// Comentario: Obtener identificador opcional de dispositivo.
$deviceUid = Validation::optionalString($payload, 'device_id', 80);

// Comentario: Añadir tipo validado al payload normalizado.
$payload['event_type'] = $eventType;

// Comentario: Añadir severidad validada al payload normalizado.
$payload['severity'] = $severity;

// Comentario: Intentar persistir evento en MySQL.
$connection = Database::tryConnection();

// Comentario: Preparar metadatos de persistencia.
$meta = ['persistence' => 'skipped'];

// Comentario: Guardar evento si hay base disponible.
if ($connection instanceof PDO) {
    // Comentario: Resolver dispositivo interno si se informó UID.
    $deviceId = $deviceUid !== null ? DeviceRepository::findIdByUid($connection, $deviceUid) : null;

    // Comentario: Insertar evento validado.
    $eventId = EventRepository::store($connection, $deviceId, $payload);

    // Comentario: Informar evento persistido.
    $meta = ['persistence' => 'stored', 'event_id' => $eventId];
}

// Comentario: Responder recepción del evento.
JsonResponse::success(
    [
        'message' => 'Evento validado.',
        'severity' => $severity,
        'event_type' => $eventType,
    ],
    $meta,
    202
);
