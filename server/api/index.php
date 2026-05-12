<?php

// Comentario: Declarar tipos estrictos para reducir errores silenciosos.
declare(strict_types=1);

// Comentario: Definir cabecera JSON para respuesta inicial de API.
header('Content-Type: application/json; charset=utf-8');

// Comentario: Devolver estado básico del backend.
echo json_encode(
    [
        'status' => 'ok',
        'project' => 'caldera-biomasa-domotica',
        'version' => '0.1.0-inicial',
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
