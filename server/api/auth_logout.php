<?php

// Comentario: Declarar tipos estrictos para cierre de sesión.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente cierre de sesión por POST.
Request::requireMethod(['POST']);

// Comentario: Iniciar sesión para poder destruirla.
AuthService::startSession();

// Comentario: Cerrar sesión actual de forma segura.
AuthService::logout();

// Comentario: Confirmar cierre de sesión.
JsonResponse::success([
    'message' => 'Sesión cerrada correctamente.',
]);
