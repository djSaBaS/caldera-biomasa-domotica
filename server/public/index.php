<?php

// Comentario: Declarar tipos estrictos para mantener código predecible.
declare(strict_types=1);

// Comentario: Cargar núcleo común para responder como JSON en la entrada pública.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Cargar la versión única del backend.
require_once __DIR__ . '/../app/ProjectVersion.php';

// Comentario: Responder estado de backend con enlace lógico a la API.
JsonResponse::success([
    'mensaje' => 'Backend de Caldera Biomasa Domótica operativo en modo seguro Sprint 02.',
    'version' => ProjectVersion::VERSION,
    'api' => '/api/index.php',
]);
