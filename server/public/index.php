<?php

// Comentario: Declarar tipos estrictos para mantener código predecible.
declare(strict_types=1);

// Comentario: Cargar núcleo común para responder como JSON en la entrada pública.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Responder estado de backend con enlace lógico a la API.
JsonResponse::success([
    'mensaje' => 'Backend inicial de Caldera Biomasa Domótica operativo en modo seguro.',
    'api' => '/api/index.php',
]);
