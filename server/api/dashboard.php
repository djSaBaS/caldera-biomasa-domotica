<?php

// Comentario: Declarar tipos estrictos para el endpoint de dashboard.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir solo consulta de dashboard mediante GET.
Request::requireMethod(['GET']);

// Comentario: Exigir sesión y rol de lectura antes de exponer datos operativos del panel.
$user = AuthorizationService::requireAnyRole(['administrador', 'operador', 'solo_lectura', 'mantenimiento']);

// Comentario: Obtener identificador opcional de dispositivo para filtrar snapshot.
$deviceUid = Request::queryString('device_id', 80);

// Comentario: Intentar conexión real a MySQL sin romper modo demo.
$connection = Database::tryConnection();

// Comentario: Construir snapshot estable desde base o fallback seguro.
$snapshot = DashboardRepository::snapshot($connection, $deviceUid);

// Comentario: Responder datos agregados del panel con metadatos de origen.
JsonResponse::success(
    $snapshot,
    [
        'persistence' => $snapshot['source'] === 'database' ? 'database' : 'fallback_safe',
        'authenticated_required' => true,
        'user_role' => (string) ($user['role'] ?? ''),
    ]
);
