<?php

// Comentario: Declarar tipos estrictos para comparación segura de claves.
declare(strict_types=1);

// Comentario: Centralizar validación de API key de dispositivo con política de fallo cerrado.
final class ApiKeyValidator
{
    // Comentario: Definir valores documentales que nunca deben aceptarse como credenciales reales.
    private const PLACEHOLDER_KEYS = ['cambiar_en_local', 'REEMPLAZAR_POR_UNA_CLAVE_LARGA_ALEATORIA', 'TU_API_KEY_AQUI'];

    // Comentario: Definir longitud mínima para reducir errores de configuración con claves triviales.
    private const MINIMUM_KEY_LENGTH = 24;

    // Comentario: Evitar instancias de utilidad estática.
    private function __construct()
    {
    }

    // Comentario: Validar cabecera `X-API-KEY` contra entorno seguro o hash almacenado en MySQL.
    public static function requireValidDeviceKey(): void
    {
        // Comentario: Leer clave recibida desde cabecera HTTP.
        $providedKey = Request::header('X-API-KEY');

        // Comentario: Validar una única identidad antes de cualquier vía de autenticación.
        self::deviceUidFromRequest();

        // Comentario: Aplicar límite amplio por API key/IP para proteger endpoints de dispositivo.
        RateLimiter::requireAllowance('device_api_ip', 240, 60);

        // Comentario: Mantener el límite adicional por clave sin permitir rotación ilimitada por IP.
        RateLimiter::requireAllowance('device_api:' . hash('sha256', $providedKey), 120, 60);

        // Comentario: Rechazar clave vacía antes de comparar.
        if (strlen($providedKey) < self::MINIMUM_KEY_LENGTH || strlen($providedKey) > 72 || in_array($providedKey, self::PLACEHOLDER_KEYS, true)) {
            JsonResponse::error('api_key_invalida', 'La clave API del dispositivo no es válida.', 401);
        }

        // Comentario: Aceptar clave de entorno solo si está configurada y no es un placeholder público.
        if (self::isValidEnvironmentKey($providedKey)) {
            return;
        }

        // Comentario: Intentar validación contra hash en MySQL si hay dispositivo informado.
        if (self::isValidDatabaseKey($providedKey)) {
            return;
        }

        // Comentario: Responder sin indicar si falló configuración, dispositivo o secreto.
        JsonResponse::error('api_key_invalida', 'La clave API del dispositivo no es válida.', 401);
    }

    // Comentario: Validar API key configurada por entorno aplicando fallo cerrado.
    private static function isValidEnvironmentKey(string $providedKey): bool
    {
        // Comentario: Reservar la clave compartida al desarrollo; producción requiere claves por dispositivo.
        if (getenv('APP_ENV') === 'production') {
            return false;
        }

        // Comentario: Leer clave esperada exclusivamente desde entorno real o `.env` local cargado.
        $expectedKey = getenv('DEVICE_API_KEY');

        // Comentario: Rechazar configuración ausente para no aceptar claves públicas por defecto.
        if (!is_string($expectedKey) || trim($expectedKey) === '') {
            return false;
        }

        // Comentario: Normalizar clave esperada antes de validarla.
        $expectedKey = trim($expectedKey);

        // Comentario: Rechazar placeholders documentados aunque coincidan con la cabecera recibida.
        if (in_array($expectedKey, self::PLACEHOLDER_KEYS, true)) {
            return false;
        }

        // Comentario: Rechazar claves demasiado cortas para reducir despliegues inseguros accidentales.
        if (strlen($expectedKey) < self::MINIMUM_KEY_LENGTH) {
            return false;
        }

        // Comentario: Comparar con hash_equals para evitar filtraciones temporales.
        return hash_equals($expectedKey, $providedKey);
    }

    // Comentario: Validar API key usando `devices.api_key_hash` cuando MySQL está disponible.
    private static function isValidDatabaseKey(string $providedKey): bool
    {
        // Comentario: Obtener UID de dispositivo desde query string o cuerpo JSON cacheado.
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

    // Comentario: Extraer device_id sin leer directamente `php://input` para no consumir el stream.
    private static function deviceUidFromRequest(): string
    {
        // Comentario: Leer device_id desde query string si existe.
        $queryDevice = Request::queryString('device_id', 80);

        // Comentario: Usar exclusivamente la query en peticiones de lectura.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            return $queryDevice;
        }

        // Comentario: Leer cuerpo JSON mediante caché centralizada de Request.
        $payload = Request::jsonBody();

        // Comentario: Validar el identificador que utilizará el endpoint para persistir datos.
        $bodyDevice = Validation::requiredString($payload, 'device_id', 'El dispositivo es obligatorio.', 80);

        // Comentario: Rechazar identidades contradictorias para impedir escritura entre dispositivos.
        if ($queryDevice !== '' && $queryDevice !== $bodyDevice) {
            JsonResponse::error('dispositivo_incoherente', 'La URL y el cuerpo deben identificar el mismo dispositivo.', 422);
        }

        // Comentario: Autenticar siempre el destino real de los datos.
        return $bodyDevice;
    }
}
