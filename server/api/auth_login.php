<?php

// Comentario: Declarar tipos estrictos para autenticación.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente login por POST.
Request::requireMethod(['POST']);

// Comentario: Iniciar sesión antes de leer o escribir identidad.
AuthService::startSession();

// Comentario: Leer JSON de credenciales.
$payload = Request::jsonBody();

// Comentario: Validar usuario o email obligatorio.
$userOrEmail = Validation::requiredString($payload, 'usuario', 'El usuario o email es obligatorio.', 180);

// Comentario: Validar contraseña obligatoria sin registrarla en logs.
$password = Validation::requiredString($payload, 'contrasena', 'La contraseña es obligatoria.', 255);

// Comentario: Intentar conexión real a MySQL.
$connection = Database::tryConnection();

// Comentario: Rechazar login real si la base de datos no está disponible.
if (!$connection instanceof PDO) {
    JsonResponse::error('base_datos_no_disponible', 'No se puede autenticar sin conexión a MySQL.', 503);
}

// Comentario: Validar credenciales con servicio de autenticación.
$user = AuthService::login($connection, $userOrEmail, $password);

// Comentario: Rechazar credenciales inválidas con mensaje genérico.
if (!is_array($user)) {
    JsonResponse::error('credenciales_invalidas', 'Usuario o contraseña no válidos.', 401);
}

// Comentario: Responder usuario autenticado sin información sensible.
JsonResponse::success([
    'message' => 'Autenticación correcta.',
    'user' => $user,
    'csrf_token' => Csrf::token(),
]);
