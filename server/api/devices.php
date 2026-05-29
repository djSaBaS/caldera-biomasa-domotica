<?php

// Comentario: Declarar tipos estrictos para administración de dispositivos.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir listado y alta/edición de dispositivos.
Request::requireMethod(['GET', 'POST']);

// Comentario: Obtener método HTTP actual.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comentario: Permitir consulta a cualquier usuario autenticado.
if ($method === 'GET') {
    AuthorizationService::requireAuthenticatedUser();
} else {
    // Comentario: Exigir administrador para modificar dispositivos.
    AuthorizationService::requireAnyRole(['administrador']);

    // Comentario: Exigir token CSRF en operaciones mutables.
    Csrf::requireValidToken();
}

// Comentario: Intentar conexión real a MySQL.
$connection = Database::tryConnection();

// Comentario: Rechazar gestión de dispositivos sin persistencia.
if (!$connection instanceof PDO) {
    JsonResponse::error('base_datos_no_disponible', 'La gestión de dispositivos requiere MySQL disponible.', 503);
}

// Comentario: Responder listado seguro en consultas GET.
if ($method === 'GET') {
    JsonResponse::success(['devices' => DeviceRepository::list($connection)], ['persistence' => 'database']);
}

// Comentario: Leer cuerpo JSON de creación o actualización.
$payload = Request::jsonBody();

// Comentario: Validar nombre lógico del dispositivo.
$name = Validation::requiredString($payload, 'name', 'El nombre del dispositivo es obligatorio.', 120);

// Comentario: Validar estado permitido.
$status = Validation::allowedString($payload, 'status', ['activo', 'inactivo', 'mantenimiento'], 'El estado del dispositivo no está permitido.');

// Comentario: Leer ubicación opcional.
$location = Validation::optionalString($payload, 'location', 160);

// Comentario: Leer versión Arduino opcional.
$firmwareArduino = Validation::optionalString($payload, 'firmware_arduino', 40);

// Comentario: Leer versión ESP32 opcional.
$firmwareEsp32 = Validation::optionalString($payload, 'firmware_esp32', 40);

// Comentario: Obtener identificador si se trata de edición.
$deviceId = (int) ($payload['id'] ?? 0);

// Comentario: Actualizar dispositivo existente sin tocar API key.
if ($deviceId > 0) {
    DeviceRepository::update($connection, $deviceId, ['name' => $name, 'location' => $location, 'firmware_arduino' => $firmwareArduino, 'firmware_esp32' => $firmwareEsp32, 'status' => $status]);

    // Comentario: Responder actualización sin datos sensibles.
    JsonResponse::success(['message' => 'Dispositivo actualizado.', 'device_id' => $deviceId]);
}

// Comentario: Validar UID lógico solo para creación.
$deviceUid = Validation::requiredString($payload, 'device_uid', 'El identificador del dispositivo es obligatorio.', 80);

// Comentario: Validar API key inicial solo para creación.
$apiKey = Validation::requiredString($payload, 'api_key', 'La API key inicial del dispositivo es obligatoria.', 255);

// Comentario: Exigir longitud mínima compatible con fallo cerrado.
if (strlen($apiKey) < 24) {
    JsonResponse::error('validacion_error', 'La API key debe tener al menos 24 caracteres.', 422, ['field' => 'api_key']);
}

// Comentario: Crear dispositivo guardando solo hash de API key.
$createdDeviceId = DeviceRepository::create($connection, ['device_uid' => $deviceUid, 'name' => $name, 'api_key_hash' => password_hash($apiKey, PASSWORD_DEFAULT), 'location' => $location, 'firmware_arduino' => $firmwareArduino, 'firmware_esp32' => $firmwareEsp32, 'status' => $status]);

// Comentario: Responder alta sin devolver la API key.
JsonResponse::success(['message' => 'Dispositivo creado.', 'device_id' => $createdDeviceId], [], 201);
