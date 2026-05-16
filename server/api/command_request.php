<?php

// Comentario: Declarar tipos estrictos para solicitudes web de comandos.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente creación de comandos por POST.
Request::requireMethod(['POST']);

// Comentario: Exigir rol operativo para solicitar comandos remotos.
$user = AuthorizationService::requireAnyRole(['administrador', 'operador', 'mantenimiento']);

// Comentario: Exigir token CSRF porque se usa sesión web.
Csrf::requireValidToken();

// Comentario: Leer cuerpo JSON de solicitud.
$payload = Request::jsonBody();

// Comentario: Validar dispositivo destino.
$deviceUid = Validation::requiredString($payload, 'device_id', 'El dispositivo destino es obligatorio.', 80);

// Comentario: Validar tipo de comando permitido.
$commandType = Validation::allowedString($payload, 'command_type', ['START', 'STOP', 'RESET_ALARM', 'ENTER_MAINTENANCE', 'EXIT_MAINTENANCE'], 'El comando solicitado no está permitido.');

// Comentario: Bloquear encendido remoto salvo habilitación explícita para banco de pruebas.
if ($commandType === 'START' && getenv('REMOTE_START_ALLOWED') !== 'true') {
    JsonResponse::error('encendido_remoto_bloqueado', 'El encendido remoto está bloqueado por seguridad en esta fase.', 403);
}

// Comentario: Bloquear encendido remoto al rol mantenimiento aunque se habilite la bandera.
if ($commandType === 'START' && (string) ($user['role'] ?? '') === 'mantenimiento') {
    JsonResponse::error('permiso_denegado', 'El rol mantenimiento no puede solicitar encendido remoto.', 403);
}

// Comentario: Intentar conexión real a MySQL para trazabilidad obligatoria.
$connection = Database::tryConnection();

// Comentario: Rechazar comandos no auditables sin base de datos.
if (!$connection instanceof PDO) {
    JsonResponse::error('base_datos_no_disponible', 'No se pueden crear comandos sin MySQL disponible.', 503);
}

// Comentario: Buscar identificador interno del dispositivo.
$deviceId = DeviceRepository::findIdByUid($connection, $deviceUid);

// Comentario: Rechazar comandos para dispositivos no registrados.
if ($deviceId === null) {
    JsonResponse::error('dispositivo_no_registrado', 'El dispositivo indicado no está registrado en MySQL.', 404);
}

// Comentario: Construir payload mínimo para que firmware vuelva a validar seguridad.
$commandPayload = json_encode(['requested_from' => 'web', 'safe_mode' => true], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

// Comentario: Insertar comando pendiente con expiración corta.
$commandId = CommandRepository::createPending($connection, ['device_id' => $deviceId, 'requested_by' => (int) $user['id'], 'command_type' => $commandType, 'payload_json' => $commandPayload]);

// Comentario: Registrar historial inicial del comando.
CommandRepository::addHistory($connection, $commandId, 'pendiente', 'Comando creado desde panel web y pendiente de validación por firmware.');

// Comentario: Responder creación auditable del comando.
JsonResponse::success(['message' => 'Comando registrado para validación local del firmware.', 'command_id' => $commandId, 'remote_start_enabled' => getenv('REMOTE_START_ALLOWED') === 'true'], [], 201);
