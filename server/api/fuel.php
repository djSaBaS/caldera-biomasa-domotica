<?php

// Comentario: Declarar tipos estrictos para gestión de combustible.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir consulta y alta inicial de combustible.
Request::requireMethod(['GET', 'POST']);

// Comentario: Detectar método actual para separar responsabilidades.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comentario: Responder listado simulado para el panel inicial.
if ($method === 'GET') {
    JsonResponse::success(
        [
            'stock_estimado_kg' => 420,
            'consumo_hoy_kg' => 18.4,
            'coste_hoy_eur' => 7.36,
            'compras' => [],
        ],
        [
            'simulation' => true,
        ]
    );
}

// Comentario: Leer alta de compra o consumo desde JSON.
$payload = Request::jsonBody();

// Comentario: Validar tipo de combustible permitido.
$fuelType = trim((string) ($payload['fuel_type'] ?? ''));

// Comentario: Rechazar tipos no contemplados inicialmente.
if (!in_array($fuelType, ['pellet', 'hueso_aceituna', 'otro'], true)) {
    JsonResponse::error('combustible_invalido', 'El tipo de combustible no está permitido.', 422);
}

// Comentario: Confirmar validación sin persistir hasta activar base de datos real.
JsonResponse::success(
    [
        'message' => 'Registro de combustible validado para persistencia futura.',
        'fuel_type' => $fuelType,
    ],
    [
        'simulation' => true,
    ],
    202
);
