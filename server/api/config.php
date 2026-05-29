<?php

// Comentario: Declarar tipos estrictos para mayor seguridad.
declare(strict_types=1);

// Comentario: Cargar núcleo común y catálogo de configuración.
require_once __DIR__ . '/../app/bootstrap.php';
// Comentario: Cargar validador/catálogo de parámetros de caldera.
require_once __DIR__ . '/../app/Services/BoilerConfigValidator.php';

// Comentario: Permitir únicamente lectura de configuración por GET.
Request::requireMethod(['GET']);

// Comentario: Exigir API key para evitar entrega de parámetros a clientes no autorizados.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Obtener identificador del dispositivo desde query string.
$deviceUid = Request::queryString('device_id');

// Comentario: Validar presencia de identificador de dispositivo.
if ($deviceUid === '') {
    JsonResponse::error('device_id_requerido', 'El parámetro device_id es obligatorio.', 422);
}

// Comentario: Preparar configuración segura de respaldo con sinfín ON igual a OFF.
$config = BoilerConfigValidator::defaultConfig($deviceUid);

// Comentario: Preparar metadatos de origen de configuración.
$meta = ['source' => 'fallback_safe'];

// Comentario: Intentar leer configuración real desde MySQL.
$connection = Database::tryConnection();

// Comentario: Usar configuración persistida si existe dispositivo y fila activa.
if ($connection instanceof PDO) {
    // Comentario: Buscar dispositivo interno por UID.
    $deviceId = DeviceRepository::findIdByUid($connection, $deviceUid);

    // Comentario: Consultar configuración solo para dispositivos registrados.
    if ($deviceId !== null) {
        // Comentario: Leer configuración activa de base de datos.
        $storedConfig = BoilerConfigRepository::findByDevice($connection, $deviceId);

        // Comentario: Sustituir respaldo si existe configuración persistida.
        if (is_array($storedConfig)) {
            $config = array_merge(['device_id' => $deviceUid], $storedConfig);
            $meta = ['source' => 'database'];
        }
    }
}

// Comentario: Responder configuración junto al catálogo validable.
JsonResponse::success(
    [
        'config' => $config,
        'catalog' => BoilerConfigValidator::catalog(),
        'safety_note' => 'El firmware vuelve a validar límites y puede rechazar cualquier parámetro inseguro.',
    ],
    $meta
);
