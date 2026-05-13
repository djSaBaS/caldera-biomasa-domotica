<?php

// Comentario: Declarar tipos estrictos para registros de mantenimiento.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir consulta y alta inicial de mantenimiento.
Request::requireMethod(['GET', 'POST']);

// Comentario: Detectar método actual para responder de forma separada.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comentario: Devolver planificación simulada para interfaz inicial.
if ($method === 'GET') {
    JsonResponse::success(
        [
            'pendientes' => [
                ['tipo' => 'limpieza', 'vencimiento' => '2026-05-20', 'prioridad' => 'aviso'],
            ],
            'historial' => [],
        ],
        [
            'simulation' => true,
        ]
    );
}

// Comentario: Leer registro de mantenimiento desde JSON.
$payload = Request::jsonBody();

// Comentario: Validar tipo de mantenimiento permitido.
$type = trim((string) ($payload['maintenance_type'] ?? ''));

// Comentario: Rechazar tipos no previstos en Sprint 01.
if (!in_array($type, ['limpieza', 'revision', 'pieza', 'reparacion'], true)) {
    JsonResponse::error('mantenimiento_invalido', 'El tipo de mantenimiento no está permitido.', 422);
}

// Comentario: Confirmar validación sin almacenar aún si no hay base importada.
JsonResponse::success(
    [
        'message' => 'Mantenimiento validado para persistencia futura.',
        'maintenance_type' => $type,
    ],
    [
        'simulation' => true,
    ],
    202
);
