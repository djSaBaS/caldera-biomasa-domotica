<?php

// Comentario: Declarar tipos estrictos para toda la carga común.
declare(strict_types=1);


// Comentario: Cargar lector de variables `.env` sin dependencias externas.
require_once __DIR__ . '/Core/Env.php';
// Comentario: Cargar archivo `.env` local si existe fuera de Git.
Env::load(__DIR__ . '/../.env');

// Comentario: Leer origen de petición para CORS de desarrollo controlado.
$origenHttp = $_SERVER['HTTP_ORIGIN'] ?? '';
// Comentario: Definir orígenes locales permitidos durante desarrollo.
$origenesPermitidos = ['http://localhost:8080', 'http://127.0.0.1:8080'];
// Comentario: Permitir credenciales solo para orígenes locales conocidos.
if (in_array($origenHttp, $origenesPermitidos, true)) {
    // Comentario: Devolver origen concreto, nunca comodín con credenciales.
    header('Access-Control-Allow-Origin: ' . $origenHttp);
    // Comentario: Permitir cookies de sesión del login PHP en desarrollo.
    header('Access-Control-Allow-Credentials: true');
    // Comentario: Permitir métodos usados por los endpoints actuales.
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // Comentario: Permitir cabeceras usadas por JSON y dispositivos.
    header('Access-Control-Allow-Headers: Content-Type, X-API-KEY, X-CSRF-TOKEN');
}
// Comentario: Responder preflight CORS sin ejecutar lógica de negocio.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    // Comentario: Indicar que la petición previa fue aceptada.
    http_response_code(204);
    // Comentario: Finalizar ejecución sin cuerpo.
    exit;
}

// Comentario: Cargar utilidades comunes de la API sin framework pesado.
require_once __DIR__ . '/Core/JsonResponse.php';
// Comentario: Cargar helper de lectura y validación de peticiones.
require_once __DIR__ . '/Core/Request.php';
// Comentario: Cargar helper centralizado de validación de datos.
require_once __DIR__ . '/Core/Validation.php';
// Comentario: Cargar validador centralizado de API key.
require_once __DIR__ . '/Core/ApiKeyValidator.php';
// Comentario: Cargar protección CSRF para operaciones web autenticadas.
require_once __DIR__ . '/Core/Csrf.php';
// Comentario: Cargar conexión PDO preparada para consultas reales o modo degradado.
require_once __DIR__ . '/Core/Database.php';
// Comentario: Cargar repositorio de dispositivos.
require_once __DIR__ . '/Services/DeviceRepository.php';
// Comentario: Cargar repositorio de telemetría.
require_once __DIR__ . '/Services/TelemetryRepository.php';
// Comentario: Cargar repositorio de configuración.
require_once __DIR__ . '/Services/BoilerConfigRepository.php';
// Comentario: Cargar repositorio de comandos.
require_once __DIR__ . '/Services/CommandRepository.php';
// Comentario: Cargar repositorio de eventos.
require_once __DIR__ . '/Services/EventRepository.php';
// Comentario: Cargar repositorio de combustible.
require_once __DIR__ . '/Services/FuelRepository.php';
// Comentario: Cargar repositorio de mantenimiento.
require_once __DIR__ . '/Services/MaintenanceRepository.php';
// Comentario: Cargar servicio de autenticación con sesiones PHP.
require_once __DIR__ . '/Services/AuthService.php';
// Comentario: Cargar autorización por roles para endpoints del panel.
require_once __DIR__ . '/Services/AuthorizationService.php';
// Comentario: Cargar repositorio de usuarios para administración segura.
require_once __DIR__ . '/Services/UserRepository.php';
