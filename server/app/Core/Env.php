<?php

// Comentario: Declarar tipos estrictos para carga de entorno.
declare(strict_types=1);

// Comentario: Cargar variables desde un archivo `.env` local sin dependencias externas.
final class Env
{
    // Comentario: Evitar instancias porque la clase solo contiene utilidades.
    private function __construct()
    {
    }

    // Comentario: Cargar variables si el archivo existe y no están definidas previamente.
    public static function load(string $path): void
    {
        // Comentario: No hacer nada si no existe archivo local.
        if (!is_file($path)) {
            return;
        }

        // Comentario: Leer líneas ignorando saltos vacíos de forma segura.
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Comentario: Validar que la lectura devolvió un array.
        if (!is_array($lines)) {
            return;
        }

        // Comentario: Procesar cada línea del archivo `.env`.
        foreach ($lines as $line) {
            // Comentario: Ignorar comentarios completos.
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Comentario: Ignorar líneas sin separador clave valor.
            if (!str_contains($line, '=')) {
                continue;
            }

            // Comentario: Separar clave y valor conservando signos igual posteriores.
            [$key, $value] = explode('=', $line, 2);

            // Comentario: Normalizar nombre de variable.
            $key = trim($key);

            // Comentario: Normalizar valor eliminando comillas exteriores simples o dobles.
            $value = trim(trim($value), "\"'");

            // Comentario: No sobrescribir variables ya definidas por el entorno real.
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}
