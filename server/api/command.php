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
$deviceUid = Request::queryString('device_id');

// Comentario: Validar que el dispositivo esté identificado.
if ($deviceUid === '') {
    JsonResponse::error('device_id_requerido', 'El parámetro device_id es obligatorio.', 422);
}

// Comentario: Mantener cola vacía por defecto para evitar acciones remotas si no hay base.
$commands = [];

// Comentario: Preparar metadatos seguros de entrega de comandos.
$meta = ['remote_start_enabled' => false, 'persistence' => 'skipped'];

// Comentario: Intentar leer comandos desde MySQL si está disponible.
$connection = Database::tryConnection();

// Comentario: Consultar comandos solo si existe conexión real.
if ($connection instanceof PDO) {
    // Comentario: Buscar dispositivo interno por UID.
    $deviceId = DeviceRepository::findIdByUid($connection, $deviceUid);

    // Comentario: Obtener comandos pendientes solo para dispositivos registrados.
    if ($deviceId !== null) {
        $commands = CommandRepository::pendingByDevice($connection, $deviceId);

        // Comentario: Extraer identificadores entregados para trazabilidad.
        $commandIds = array_map(static fn (array $command): int => (int) $command['id'], $commands);

        // Comentario: Marcar comandos como enviados tras entregarlos.
        CommandRepository::markAsSent($connection, $commandIds);

        // Comentario: Indicar persistencia consultada aunque no haya comandos.
        $meta = ['remote_start_enabled' => false, 'persistence' => 'database'];
    }
}

// Comentario: Responder cola de comandos, vacía si no hay órdenes validadas.
JsonResponse::success(
    [
        'device_id' => $deviceUid,
        'commands' => $commands,
        'message' => 'Los comandos remotos siguen sujetos a validación local del firmware.',
    ],
    $meta
);
