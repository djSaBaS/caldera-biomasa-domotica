<?php

// Comentario: Declarar tipos estrictos para emisión de token CSRF.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir únicamente consulta por GET.
Request::requireMethod(['GET']);

// Comentario: Exigir sesión autenticada antes de entregar token CSRF.
$user = AuthorizationService::requireAuthenticatedUser();

// Comentario: Responder token asociado a la sesión actual.
JsonResponse::success([
    'csrf_token' => Csrf::token(),
    'user' => $user,
]);
