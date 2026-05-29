<?php

// Comentario: Declarar tipos estrictos para administración de usuarios.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir listado y alta/edición de usuarios.
Request::requireMethod(['GET', 'POST']);

// Comentario: Exigir rol administrador para cualquier gestión de usuarios.
AuthorizationService::requireAnyRole(['administrador']);

// Comentario: Intentar conexión real a MySQL.
$connection = Database::tryConnection();

// Comentario: Rechazar gestión de usuarios sin persistencia.
if (!$connection instanceof PDO) {
    JsonResponse::error('base_datos_no_disponible', 'La gestión de usuarios requiere MySQL disponible.', 503);
}

// Comentario: Obtener método HTTP actual.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comentario: Devolver listado seguro en consultas GET.
if ($method === 'GET') {
    JsonResponse::success(['users' => UserRepository::list($connection)], ['persistence' => 'database']);
}

// Comentario: Exigir token CSRF en operaciones mutables.
Csrf::requireValidToken();

// Comentario: Leer cuerpo JSON de creación o actualización.
$payload = Request::jsonBody();

// Comentario: Validar nombre visible del usuario.
$name = Validation::requiredString($payload, 'name', 'El nombre del usuario es obligatorio.', 120);

// Comentario: Validar email obligatorio.
$email = Validation::requiredString($payload, 'email', 'El email del usuario es obligatorio.', 180);

// Comentario: Rechazar email con formato inválido.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    JsonResponse::error('validacion_error', 'El formato del email no es válido.', 422, ['field' => 'email']);
}

// Comentario: Validar nombre de usuario obligatorio.
$username = Validation::requiredString($payload, 'username', 'El nombre de usuario es obligatorio.', 80);

// Comentario: Validar rol funcional permitido.
$roleCode = Validation::allowedString($payload, 'role', ['administrador', 'operador', 'solo_lectura', 'mantenimiento'], 'El rol indicado no está permitido.');

// Comentario: Validar estado permitido.
$status = Validation::allowedString($payload, 'status', ['activo', 'inactivo', 'bloqueado'], 'El estado indicado no está permitido.');

// Comentario: Obtener identificador si se trata de edición.
$userId = (int) ($payload['id'] ?? 0);

// Comentario: Actualizar usuario existente si hay identificador positivo.
if ($userId > 0) {
    UserRepository::update($connection, $userId, ['name' => $name, 'email' => $email, 'username' => $username, 'role_code' => $roleCode, 'status' => $status]);

    // Comentario: Responder actualización sin datos sensibles.
    JsonResponse::success(['message' => 'Usuario actualizado.', 'user_id' => $userId]);
}

// Comentario: Validar contraseña inicial solo en creación.
$password = Validation::requiredString($payload, 'password', 'La contraseña inicial es obligatoria.', 255);

// Comentario: Exigir longitud mínima razonable.
if (strlen($password) < 12) {
    JsonResponse::error('validacion_error', 'La contraseña debe tener al menos 12 caracteres.', 422, ['field' => 'password']);
}

// Comentario: Crear usuario con hash seguro de contraseña.
$createdUserId = UserRepository::create($connection, ['name' => $name, 'email' => $email, 'username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role_code' => $roleCode, 'status' => $status]);

// Comentario: Responder creación sin exponer contraseña ni hash.
JsonResponse::success(['message' => 'Usuario creado.', 'user_id' => $createdUserId], [], 201);
