<?php

// Comentario: Declarar tipos estrictos para evitar conversiones implícitas no deseadas.
declare(strict_types=1);

// Comentario: Definir una utilidad centralizada para respuestas JSON consistentes.
final class JsonResponse
{
    // Comentario: Evitar instancias de una clase puramente estática.
    private function __construct()
    {
    }

    // Comentario: Enviar una respuesta correcta con datos y metadatos opcionales.
    public static function success(array $data = [], array $meta = [], int $statusCode = 200): void
    {
        // Comentario: Delegar el envío real en un método común para reducir duplicación.
        self::send(true, $data, null, $meta, $statusCode);
    }

    // Comentario: Enviar una respuesta de error con código, mensaje y detalles opcionales.
    public static function error(string $code, string $message, int $statusCode = 400, array $details = []): void
    {
        // Comentario: Construir el bloque de error sin exponer información sensible.
        $error = [
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ];

        // Comentario: Delegar el envío real en un método común para mantener formato estable.
        self::send(false, [], $error, [], $statusCode);
    }

    // Comentario: Emitir cabeceras, código HTTP y cuerpo JSON final.
    private static function send(bool $success, array $data, ?array $error, array $meta, int $statusCode): void
    {
        // Comentario: Establecer código HTTP antes de imprimir el cuerpo.
        http_response_code($statusCode);

        // Comentario: Declarar formato JSON UTF-8 para todos los consumidores.
        header('Content-Type: application/json; charset=utf-8');

        // Comentario: Convertir arrays vacíos a objetos JSON para cumplir el contrato documentado.
        $safeData = $data === [] ? new stdClass() : $data;

        // Comentario: Convertir metadatos vacíos a objeto JSON para mantener formato estable.
        $safeMeta = $meta === [] ? new stdClass() : $meta;

        // Comentario: Preparar el contrato común de respuesta de la API.
        $payload = [
            'success' => $success,
            'data' => $safeData,
            'error' => $error,
            'meta' => $safeMeta,
        ];

        // Comentario: Codificar sin escapar caracteres españoles para facilitar depuración.
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Comentario: Finalizar ejecución para evitar salidas accidentales posteriores.
        exit;
    }
}
