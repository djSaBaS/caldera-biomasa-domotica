<?php

// Comentario: Declarar tipos estrictos para eventos del sistema.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir alta de evento en modo API base.
Request::requireMethod(['POST']);

// Comentario: Exigir clave API para eventos originados por dispositivo.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Leer payload JSON validado.
$payload = Request::jsonBody();

// Comentario: Validar severidad contra catálogo permitido.
$severity = trim((string) ($payload['severity'] ?? 'info'));

// Comentario: Definir severidades admitidas.
$allowedSeverities = ['info', 'aviso', 'error', 'critico'];

// Comentario: Rechazar severidades desconocidas.
if (!in_array($severity, $allowedSeverities, true)) {
    JsonResponse::error('severidad_invalida', 'La severidad indicada no está permitida.', 422);
}

// Comentario: Responder recepción del evento para persistencia futura.
JsonResponse::success(
    [
        'message' => 'Evento validado y preparado para persistencia.',
        'severity' => $severity,
    ],
    [
        'simulation' => true,
    ],
    202
);
