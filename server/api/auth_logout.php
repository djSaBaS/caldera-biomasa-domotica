<?php

// Comentario: Declarar tipos estrictos para cierre de sesión.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente cierre de sesión por POST.
Request::requireMethod(['POST']);

// Comentario: Exigir sesión existente antes de cerrar.
AuthorizationService::requireAuthenticatedUser();

// Comentario: Exigir CSRF para evitar cierres inducidos por terceros.
Csrf::requireValidToken();

// Comentario: Cerrar sesión actual de forma segura.
AuthService::logout();

// Comentario: Confirmar cierre de sesión.
JsonResponse::success([
    'message' => 'Sesión cerrada correctamente.',
]);
