<?php

// Comentario: Declarar tipos estrictos para evitar conversiones no deseadas.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Aceptar únicamente telemetría enviada por POST.
Request::requireMethod(['POST']);

// Comentario: Exigir API key de dispositivo para telemetría.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Leer cuerpo JSON validado.
$payload = Request::jsonBody();

// Comentario: Validar identificador de dispositivo obligatorio.
$deviceUid = Validation::requiredString($payload, 'device_id', 'El campo device_id es obligatorio.', 80);

// Comentario: Validar estado de caldera contra estados originales conocidos.
$state = strtoupper(trim((string) ($payload['state'] ?? 'OFF')));

// Comentario: Definir estados permitidos por la lógica original documentada.
$allowedStates = ['OFF', 'CHECK', 'ACC', 'STB', 'NORMAL', 'MOD', 'MAN', 'SIC', 'SPE', 'ALT'];

// Comentario: Rechazar estados desconocidos para no contaminar históricos.
if (!in_array($state, $allowedStates, true)) {
    JsonResponse::error('estado_invalido', 'El estado de caldera recibido no está permitido.', 422);
}

// Comentario: Intentar conexión a MySQL para persistencia real.
$connection = Database::tryConnection();

// Comentario: Preparar metadatos iniciales de persistencia.
$meta = ['persistence' => 'skipped'];

// Comentario: Guardar telemetría si existe base y dispositivo registrado.
if ($connection instanceof PDO) {
    // Comentario: Buscar dispositivo interno por UID.
    $deviceId = DeviceRepository::findIdByUid($connection, $deviceUid);

    // Comentario: Persistir solo si el dispositivo está registrado.
    if ($deviceId !== null) {
        // Comentario: Guardar muestra de telemetría validada.
        $telemetryId = TelemetryRepository::store($connection, $deviceId, $payload, $state);

        // Comentario: Actualizar metadatos de persistencia real.
        $meta = ['persistence' => 'stored', 'telemetry_id' => $telemetryId];
    } else {
        // Comentario: Informar modo degradado por dispositivo no registrado.
        $meta = ['persistence' => 'skipped', 'reason' => 'device_not_registered'];
    }
}

// Comentario: Responder confirmación segura de telemetría.
JsonResponse::success(
    [
        'message' => 'Telemetría validada.',
        'device_id' => $deviceUid,
        'state' => $state,
    ],
    $meta,
    202
);
