<?php

// Comentario: Declarar tipos estrictos para registros de mantenimiento.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir consulta y alta inicial de mantenimiento.
Request::requireMethod(['GET', 'POST']);

// Comentario: Detectar método actual para responder de forma separada.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comentario: Intentar conexión real a MySQL.
$connection = Database::tryConnection();

// Comentario: Devolver datos persistidos si MySQL está disponible.
if ($method === 'GET' && $connection instanceof PDO) {
    JsonResponse::success(['historial' => MaintenanceRepository::latest($connection)], ['persistence' => 'database']);
}

// Comentario: Devolver planificación simulada para interfaz si no hay base.
if ($method === 'GET') {
    JsonResponse::success(
        [
            'pendientes' => [
                ['tipo' => 'limpieza', 'vencimiento' => '2026-05-20', 'prioridad' => 'aviso'],
            ],
            'historial' => [],
        ],
        [
            'persistence' => 'fallback_safe',
        ]
    );
}

// Comentario: Leer registro de mantenimiento desde JSON.
$payload = Request::jsonBody();

// Comentario: Validar tipo de mantenimiento permitido.
$type = Validation::allowedString($payload, 'maintenance_type', ['limpieza', 'revision', 'pieza', 'reparacion'], 'El tipo de mantenimiento no está permitido.');

// Comentario: Validar fecha de mantenimiento.
$date = Validation::date($payload, 'maintenance_date', 'La fecha de mantenimiento debe tener formato YYYY-MM-DD.');

// Comentario: Validar descripción obligatoria.
$description = Validation::requiredString($payload, 'description', 'La descripción del mantenimiento es obligatoria.', 1000);

// Comentario: Obtener UID de dispositivo con valor seguro por defecto.
$deviceUid = Validation::requiredString($payload, 'device_id', 'El campo device_id es obligatorio.', 80);

// Comentario: Validar coste dentro de límites razonables.
$cost = is_numeric($payload['cost'] ?? null) ? Validation::decimalRange($payload, 'cost', 0, 100000, 'El coste debe ser válido.') : 0.0;

// Comentario: Persistir mantenimiento si hay base y dispositivo registrado.
if ($connection instanceof PDO) {
    $deviceId = DeviceRepository::findIdByUid($connection, $deviceUid);

    // Comentario: Rechazar persistencia si el dispositivo no existe.
    if ($deviceId === null) {
        JsonResponse::error('dispositivo_no_registrado', 'El dispositivo indicado no está registrado en MySQL.', 404);
    }

    // Comentario: Insertar registro de mantenimiento validado.
    $maintenanceId = MaintenanceRepository::insert($connection, [
        'device_id' => $deviceId,
        'maintenance_type' => $type,
        'maintenance_date' => $date,
        'description' => $description,
        'cost' => $cost,
        'replaced_parts' => Validation::optionalString($payload, 'replaced_parts', 1000),
        'technician' => Validation::optionalString($payload, 'technician', 160),
        'next_review_date' => Validation::optionalString($payload, 'next_review_date', 10),
    ]);

    // Comentario: Responder creación persistida.
    JsonResponse::success(['message' => 'Mantenimiento registrado.', 'maintenance_id' => $maintenanceId], ['persistence' => 'stored'], 201);
}

// Comentario: Confirmar validación sin almacenar si no hay base.
JsonResponse::success(['message' => 'Mantenimiento validado sin persistencia por falta de MySQL.', 'maintenance_type' => $type], ['persistence' => 'skipped'], 202);
