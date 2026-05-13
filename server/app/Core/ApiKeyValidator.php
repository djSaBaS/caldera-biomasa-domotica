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

    // Comentario: Validar cabecera `X-API-KEY` contra la clave configurada en entorno.
    public static function requireValidDeviceKey(): void
    {
        // Comentario: Leer clave recibida desde cabecera HTTP.
        $providedKey = Request::header('X-API-KEY');

        // Comentario: Leer clave esperada desde entorno con placeholder de desarrollo.
        $expectedKey = getenv('DEVICE_API_KEY') ?: 'cambiar_en_local';

        // Comentario: Rechazar clave vacía o distinta usando comparación resistente a timing simple.
        if ($providedKey === '' || !hash_equals($expectedKey, $providedKey)) {
            // Comentario: Responder sin indicar si la clave existe o no.
            JsonResponse::error('api_key_invalida', 'La clave API del dispositivo no es válida.', 401);
        }
    }
}
