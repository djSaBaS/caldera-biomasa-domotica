<?php

// Comentario: Declarar tipos estrictos para consulta de sesión.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente consulta de sesión por GET.
Request::requireMethod(['GET']);

// Comentario: Iniciar sesión para consultar identidad.
AuthService::startSession();

// Comentario: Obtener usuario actual desde sesión.
$user = AuthService::currentUser();

// Comentario: Rechazar si no existe sesión autenticada.
if (!is_array($user)) {
    JsonResponse::error('no_autenticado', 'No hay una sesión autenticada.', 401);
}

// Comentario: Devolver datos mínimos de sesión.
JsonResponse::success([
    'user' => $user,
]);
