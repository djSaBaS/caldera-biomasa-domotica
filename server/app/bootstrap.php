<?php

// Comentario: Declarar tipos estrictos para toda la carga común.
declare(strict_types=1);

// Comentario: Cargar utilidades comunes de la API sin framework pesado.
require_once __DIR__ . '/Core/JsonResponse.php';
// Comentario: Cargar helper de lectura y validación de peticiones.
require_once __DIR__ . '/Core/Request.php';
// Comentario: Cargar validador centralizado de API key.
require_once __DIR__ . '/Core/ApiKeyValidator.php';
// Comentario: Cargar conexión PDO preparada para futuras consultas reales.
require_once __DIR__ . '/Core/Database.php';
