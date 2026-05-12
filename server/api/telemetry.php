<?php

// Comentario: Declarar tipos estrictos para evitar conversiones no deseadas.
declare(strict_types=1);

// Comentario: Responder siempre en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Comentario: Permitir únicamente método POST para recepción de telemetría.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Comentario: Leer cuerpo bruto de la petición.
$rawBody = file_get_contents('php://input');

// Comentario: Decodificar JSON recibido.
$payload = json_decode($rawBody ?: '', true);

// Comentario: Validar que el cuerpo recibido sea JSON válido.
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Comentario: Responder confirmación temporal hasta implementar persistencia MySQL.
echo json_encode(
    [
        'status' => 'received',
        'message' => 'Telemetría recibida pendiente de persistencia',
    ],
    JSON_UNESCAPED_UNICODE
);
