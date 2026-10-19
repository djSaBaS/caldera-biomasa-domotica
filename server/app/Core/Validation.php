<?php

// Comentario: Declarar tipos estrictos para validaciones predecibles.
declare(strict_types=1);

// Comentario: Centralizar validaciones reutilizables de entrada.
final class Validation
{
    // Comentario: Interpretar ausencia de id como alta y rechazar conversiones implícitas al editar.
    public static function optionalId(array $payload): int
    {
        // Comentario: Reservar cero únicamente para una solicitud sin identificador de edición.
        if (!array_key_exists('id', $payload)) {
            return 0;
        }
        // Comentario: Exigir un entero positivo representable por PHP antes de consultar MySQL.
        $value = $payload['id'];
        // Comentario: No admitir arrays, booleanos ni fracciones que PHP convertiría a otro registro.
        if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            JsonResponse::error('validacion_error', 'El id de edición debe ser un entero positivo.', 422);
        }
        // Comentario: Convertir solo el identificador completo ya validado.
        return (int) $value;
    }

    // Comentario: Conservar secretos exactamente como se escriben, sin normalizar espacios ni truncar bcrypt.
    public static function requiredSecret(array $payload, string $key): string
    {
        // Comentario: Validar el tipo y el límite de bytes admitido por PASSWORD_DEFAULT actual.
        $secret = $payload[$key] ?? null;
        // Comentario: Evitar equivalencias de contraseñas por recorte o por límite de bcrypt.
        if (!is_string($secret) || $secret === '' || strlen($secret) > 72 || str_contains($secret, "\0")) {
            JsonResponse::error('validacion_error', 'El secreto debe contener entre 1 y 72 bytes válidos.', 422, ['field' => $key]);
        }
        // Comentario: Devolver el secreto íntegro sin incluirlo en diagnósticos.
        return $secret;
    }

    // Comentario: Evitar instancias porque la clase solo contiene utilidades.
    private function __construct()
    {
    }

    // Comentario: Obtener texto obligatorio con longitud máxima controlada.
    public static function requiredString(array $payload, string $key, string $message, int $maxLength = 180): string
    {
        // Comentario: Leer valor de entrada con cadena vacía como defecto.
        $value = $payload[$key] ?? null;

        // Comentario: Rechazar arrays, objetos y escalares ajenos al contrato de texto.
        if (!is_string($value) || strlen($value) > $maxLength) {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key]);
        }

        // Comentario: Normalizar texto sin truncar su identidad.
        $value = trim($value);

        // Comentario: Rechazar valores vacíos para campos obligatorios.
        if ($value === '') {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key]);
        }

        // Comentario: Limitar longitud para evitar cargas inesperadas.
        return $value;
    }

    // Comentario: Obtener texto opcional con longitud máxima controlada.
    public static function optionalString(array $payload, string $key, int $maxLength = 255): ?string
    {
        // Comentario: Devolver nulo si el campo no existe o está vacío.
        if (!isset($payload[$key]) || $payload[$key] === '') {
            return null;
        }

        // Comentario: Normalizar y limitar texto opcional.
        return self::requiredString($payload, $key, 'El texto opcional no tiene un formato válido.', $maxLength);
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
        if (!is_finite($number) || $number < $min || $number > $max) {
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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || !checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
            JsonResponse::error('validacion_error', $message, 422, ['field' => $key]);
        }

        // Comentario: Devolver fecha validada formalmente.
        return $value;
    }
}
