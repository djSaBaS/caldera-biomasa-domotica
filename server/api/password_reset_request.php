<?php

// Comentario: Declarar tipos estrictos para recuperación de contraseña.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente solicitud por POST.
Request::requireMethod(['POST']);

// Comentario: Leer JSON de solicitud.
$payload = Request::jsonBody();

// Comentario: Validar email como texto obligatorio.
$email = Validation::requiredString($payload, 'email', 'El email es obligatorio.', 180);

// Comentario: Validar formato de email antes de procesar.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    JsonResponse::error('email_invalido', 'El formato del email no es válido.', 422);
}

// Comentario: Intentar conexión real a MySQL.
$connection = Database::tryConnection();

// Comentario: Registrar token solo si la base está disponible.
if ($connection instanceof PDO) {
    AuthService::requestPasswordReset($connection, $email);
}

// Comentario: Responder siempre igual para no revelar si el email existe.
JsonResponse::success([
    'message' => 'Si el email existe, se preparará un enlace de restablecimiento en una fase con correo real.',
]);
