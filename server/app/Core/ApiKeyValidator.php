<?php

// Comentario: Declarar tipos estrictos para comparación segura de claves.
declare(strict_types=1);

// Comentario: Centralizar validación de API key de dispositivo.
final class ApiKeyValidator
{
    // Comentario: Evitar instancias de utilidad estática.
    private function __construct()
    {
    }

    // Comentario: Validar cabecera `X-API-KEY` contra entorno o hash almacenado en MySQL.
    public static function requireValidDeviceKey(): void
    {
        // Comentario: Leer clave recibida desde cabecera HTTP.
        $providedKey = Request::header('X-API-KEY');

        // Comentario: Rechazar clave vacía antes de comparar.
        if ($providedKey === '') {
            JsonResponse::error('api_key_invalida', 'La clave API del dispositivo no es válida.', 401);
        }

        // Comentario: Leer clave esperada desde entorno con placeholder de desarrollo.
        $expectedKey = getenv('DEVICE_API_KEY') ?: 'cambiar_en_local';

        // Comentario: Aceptar clave de entorno para entornos locales sin MySQL.
        if (hash_equals($expectedKey, $providedKey)) {
            return;
        }

        // Comentario: Intentar validación contra hash en MySQL si hay dispositivo informado.
        if (self::isValidDatabaseKey($providedKey)) {
            return;
        }

        // Comentario: Responder sin indicar si el dispositivo existe o no.
        JsonResponse::error('api_key_invalida', 'La clave API del dispositivo no es válida.', 401);
    }

    // Comentario: Validar API key usando `devices.api_key_hash` cuando MySQL está disponible.
    private static function isValidDatabaseKey(string $providedKey): bool
    {
        // Comentario: Obtener UID de dispositivo desde query string o cuerpo JSON.
        $deviceUid = self::deviceUidFromRequest();

        // Comentario: No validar contra base si no hay dispositivo informado.
        if ($deviceUid === '') {
            return false;
        }

        // Comentario: Intentar conexión sin romper modo degradado.
        $connection = Database::tryConnection();

        // Comentario: No validar contra base si MySQL no está disponible.
        if (!$connection instanceof PDO) {
            return false;
        }

        // Comentario: Preparar consulta del hash asociado al dispositivo activo.
        $statement = $connection->prepare("SELECT api_key_hash FROM devices WHERE device_uid = :device_uid AND status = 'activo' LIMIT 1");

        // Comentario: Ejecutar consulta con UID parametrizado.
        $statement->execute(['device_uid' => $deviceUid]);

        // Comentario: Obtener fila si existe.
        $row = $statement->fetch();

        // Comentario: Rechazar si no existe dispositivo activo.
        if (!is_array($row)) {
            return false;
        }

        // Comentario: Verificar API key contra hash de base de datos.
        return password_verify($providedKey, (string) $row['api_key_hash']);
    }

    // Comentario: Extraer device_id sin depender del orden de lectura del endpoint.
    private static function deviceUidFromRequest(): string
    {
        // Comentario: Leer device_id desde query string si existe.
        $queryDevice = Request::queryString('device_id');

        // Comentario: Devolver valor de query si está presente.
        if ($queryDevice !== '') {
            return $queryDevice;
        }

        // Comentario: Leer cuerpo bruto para endpoints POST.
        $rawBody = file_get_contents('php://input') ?: '';

        // Comentario: Decodificar JSON de forma tolerante.
        $payload = json_decode($rawBody, true);

        // Comentario: Rechazar cuerpos no asociativos.
        if (!is_array($payload)) {
            return '';
        }

        // Comentario: Devolver device_id normalizado si existe.
        return trim((string) ($payload['device_id'] ?? ''));
    }
}
