<?php

// Comentario: Declarar tipos estrictos para gestión de combustible.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Permitir consulta y alta inicial de combustible.
Request::requireMethod(['GET', 'POST']);

// Comentario: Detectar método actual para separar responsabilidades.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comentario: Intentar conexión real a MySQL.
$connection = Database::tryConnection();

// Comentario: Responder listado real si la base está disponible.
if ($method === 'GET' && $connection instanceof PDO) {
    JsonResponse::success(FuelRepository::summary($connection), ['persistence' => 'database']);
}

// Comentario: Responder listado simulado si no hay base disponible.
if ($method === 'GET') {
    JsonResponse::success(
        [
            'stock_estimado_kg' => 420,
            'consumo_hoy_kg' => 18.4,
            'coste_hoy_eur' => 7.36,
            'compras' => [],
        ],
        [
            'persistence' => 'fallback_safe',
        ]
    );
}

// Comentario: Leer alta de compra desde JSON.
$payload = Request::jsonBody();

// Comentario: Validar tipo de combustible permitido.
$fuelType = Validation::allowedString($payload, 'fuel_type', ['pellet', 'hueso_aceituna', 'otro'], 'El tipo de combustible no está permitido.');

// Comentario: Validar fecha de compra.
$purchaseDate = Validation::date($payload, 'purchase_date', 'La fecha de compra debe tener formato YYYY-MM-DD.');

// Comentario: Validar kilos comprados.
$kgPurchased = Validation::decimalRange($payload, 'kg_purchased', 0.01, 50000, 'Los kg comprados deben ser mayores que cero.');

// Comentario: Validar precio total.
$totalPrice = Validation::decimalRange($payload, 'total_price', 0, 1000000, 'El precio total debe ser un número válido.');

// Comentario: Preparar datos persistibles.
$data = [
    'fuel_type' => $fuelType,
    'purchase_date' => $purchaseDate,
    'kg_purchased' => $kgPurchased,
    'total_price' => $totalPrice,
    'supplier' => Validation::optionalString($payload, 'supplier', 160),
    'notes' => Validation::optionalString($payload, 'notes', 1000),
];

// Comentario: Persistir compra si la base está disponible.
if ($connection instanceof PDO) {
    $purchaseId = FuelRepository::insertPurchase($connection, $data);
    JsonResponse::success(['message' => 'Compra de combustible registrada.', 'purchase_id' => $purchaseId], ['persistence' => 'stored'], 201);
}

// Comentario: Confirmar validación sin persistencia cuando no hay base.
JsonResponse::success(['message' => 'Compra de combustible validada sin persistencia por falta de MySQL.', 'fuel_type' => $fuelType], ['persistence' => 'skipped'], 202);
