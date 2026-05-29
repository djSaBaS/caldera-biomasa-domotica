<?php

// Comentario: Declarar tipos estrictos para validaciones predecibles.
declare(strict_types=1);

// Comentario: Centralizar validaciones reutilizables de entrada.
final class Validation
{
    // Comentario: Evitar instancias porque la clase solo contiene utilidades.
    private function __construct()
    {
    }

    // Comentario: Obtener texto obligatorio con longitud máxima controlada.
    public static function requiredString(array $payload, string $key, string $message, int $maxLength = 180): string
    {
        // Comentario: Leer valor de entrada con cadena vacía como defecto.
        $value = trim((string) ($payload[$key] ?? ''));

        // Comentario: Rechazar valores vacíos para campos obligatorios.
        if ($value === '') {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key]);
        }

        // Comentario: Limitar longitud para evitar cargas inesperadas.
        return substr($value, 0, $maxLength);
    }

    // Comentario: Obtener texto opcional con longitud máxima controlada.
    public static function optionalString(array $payload, string $key, int $maxLength = 255): ?string
    {
        // Comentario: Devolver nulo si el campo no existe o está vacío.
        if (!isset($payload[$key]) || trim((string) $payload[$key]) === '') {
            return null;
        }

        // Comentario: Normalizar y limitar texto opcional.
        return substr(trim((string) $payload[$key]), 0, $maxLength);
    }

    // Comentario: Validar que un valor pertenece a una lista cerrada.
    public static function allowedString(array $payload, string $key, array $allowedValues, string $message): string
    {
        // Comentario: Obtener valor obligatorio antes de comparar catálogo.
        $value = self::requiredString($payload, $key, $message);

        // Comentario: Rechazar valores que no estén en el catálogo permitido.
        if (!in_array($value, $allowedValues, true)) {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key, 'allowed' => $allowedValues]);
        }

        // Comentario: Devolver valor validado.
        return $value;
    }

    // Comentario: Validar número decimal dentro de límites.
    public static function decimalRange(array $payload, string $key, float $min, float $max, string $message): float
    {
        // Comentario: Leer valor bruto del payload.
        $value = $payload[$key] ?? null;

        // Comentario: Rechazar valores no numéricos.
        if (!is_numeric($value)) {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key]);
        }

        // Comentario: Convertir a decimal para comparación segura.
        $number = (float) $value;

        // Comentario: Rechazar valores fuera de límites.
        if ($number < $min || $number > $max) {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key, 'min' => $min, 'max' => $max]);
        }

        // Comentario: Devolver decimal validado.
        return $number;
    }

    // Comentario: Validar fecha ISO básica `YYYY-MM-DD`.
    public static function date(array $payload, string $key, string $message): string
    {
        // Comentario: Obtener texto obligatorio para la fecha.
        $value = self::requiredString($payload, $key, $message, 10);

        // Comentario: Validar formato de fecha sin aceptar textos ambiguos.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key]);
        }

        // Comentario: Devolver fecha validada formalmente.
        return $value;
    }
}
