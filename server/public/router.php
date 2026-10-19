<?php

// Comentario: Mantener tipos estrictos en la única entrada HTTP de desarrollo.
declare(strict_types=1);

// Comentario: Extraer la ruta sin interpretar la query como parte del fichero.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Comentario: Publicar exclusivamente endpoints PHP directos del directorio API.
if (is_string($path) && preg_match('~^/api/[a-z_]+\.php$~D', $path)) {
    // Comentario: Resolver el fichero dentro del directorio controlado, sin segmentos de navegación.
    $endpoint = __DIR__ . '/../api/' . basename($path);
    // Comentario: Ejecutar solo endpoints existentes sin delegar al servidor de archivos estáticos.
    if (is_file($endpoint)) {
        require $endpoint;
        exit;
    }
}

// Comentario: Ofrecer el estado público únicamente en la raíz.
if ($path === '/') {
    require __DIR__ . '/index.php';
    exit;
}

// Comentario: Denegar archivos internos incluso si PHP embebido podría servirlos como estáticos.
http_response_code(404);
// Comentario: Responder sin reflejar la ruta solicitada.
header('Content-Type: application/json; charset=utf-8');
// Comentario: Mantener un error mínimo fuera del enrutamiento de negocio.
echo '{"success":false,"data":{},"error":{"code":"ruta_no_publica","message":"Ruta no disponible."},"meta":{}}';
