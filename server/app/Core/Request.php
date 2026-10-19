<?php

// Comentario: Declarar tipos estrictos para robustez en lectura de peticiones.
declare(strict_types=1);

// Comentario: Agrupar funciones seguras de entrada HTTP.
final class Request
{
    // Comentario: Evitar instancias porque la clase solo contiene utilidades.
    private function __construct()
    {
    }

    // Comentario: Validar que el método HTTP recibido coincide con los permitidos.
    public static function requireMethod(array $allowedMethods): void
    {
        // Comentario: Obtener método actual con valor seguro por defecto.
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Comentario: Permitir solo métodos declarados por cada endpoint.
        if (!in_array($method, $allowedMethods, true)) {
            // Comentario: Responder error estándar sin ejecutar lógica adicional.
            JsonResponse::error('metodo_no_permitido', 'Método HTTP no permitido para este endpoint.', 405);
        }
    }

    // Comentario: Leer JSON del cuerpo y validar que sea un objeto asociativo.
    public static function jsonBody(): array
    {
        // Comentario: Cachear el payload porque `php://input` solo debe leerse una vez por petición.
        static $cachedPayload = null;

        // Comentario: Devolver el JSON previamente decodificado si otro servicio ya lo leyó.
        if ($cachedPayload !== null) {
            return $cachedPayload;
        }

        // Comentario: Leer cuerpo bruto desde el stream estándar de PHP una única vez.
        $rawBody = file_get_contents('php://input', false, null, 0, 16385);

        // Comentario: Limitar el cuerpo antes de decodificar para proteger memoria y profundidad.
        if (!is_string($rawBody) || strlen($rawBody) > 16384) {
            JsonResponse::error('cuerpo_demasiado_grande', 'El cuerpo supera el límite de 16 KiB.', 413);
        }

        // Comentario: Decodificar JSON como array asociativo.
        $decoded = json_decode($rawBody, false, 32);

        // Comentario: Rechazar cuerpos vacíos o JSON inválido.
        if (!$decoded instanceof stdClass || json_last_error() !== JSON_ERROR_NONE) {
            // Comentario: Enviar respuesta homogénea para errores de validación.
            JsonResponse::error('json_invalido', 'El cuerpo de la petición debe ser JSON válido.', 400);
        }

        // Comentario: Conservar objetos anidados como arrays después de comprobar la raíz.
        $payload = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);

        // Comentario: Guardar payload validado para lecturas posteriores dentro de la misma petición.
        $cachedPayload = $payload;

        // Comentario: Devolver payload validado como array.
        return $cachedPayload;
    }

    // Comentario: Obtener un parámetro de consulta saneado como texto corto.
    public static function queryString(string $name, int $maxLength = 120): string
    {
        // Comentario: Leer el valor bruto desde query string.
        $value = $_GET[$name] ?? '';

        // Comentario: Rechazar valores no escalares para evitar entradas inesperadas.
        if (!is_scalar($value)) {
            // Comentario: Devolver cadena vacía ante formato incorrecto.
            return '';
        }

        // Comentario: Normalizar espacios laterales y limitar longitud sin requerir extensiones adicionales.
        $text = trim((string) $value);

        // Comentario: Evitar alias accidentales de identificadores truncados.
        if (strlen($text) > $maxLength) {
            JsonResponse::error('validacion_error', 'El parámetro de consulta es demasiado largo.', 422);
        }

        // Comentario: Devolver el valor íntegro validado.
        return $text;
    }

    // Comentario: Obtener una cabecera HTTP de forma compatible con servidores comunes.
    public static function header(string $name): string
    {
        // Comentario: Convertir nombre de cabecera al formato usado por PHP en `$_SERVER`.
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        // Comentario: Devolver cabecera normalizada o cadena vacía si no existe.
        return trim((string) ($_SERVER[$serverKey] ?? ''));
    }
}
