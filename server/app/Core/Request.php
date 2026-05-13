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
        // Comentario: Leer cuerpo bruto desde el stream estándar de PHP.
        $rawBody = file_get_contents('php://input');

        // Comentario: Decodificar JSON como array asociativo.
        $payload = json_decode($rawBody ?: '', true);

        // Comentario: Rechazar cuerpos vacíos o JSON inválido.
        if (!is_array($payload)) {
            // Comentario: Enviar respuesta homogénea para errores de validación.
            JsonResponse::error('json_invalido', 'El cuerpo de la petición debe ser JSON válido.', 400);
        }

        // Comentario: Devolver payload validado como array.
        return $payload;
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
        return substr(trim((string) $value), 0, $maxLength);
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
