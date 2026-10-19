<?php

// Comentario: Evitar conversiones silenciosas al normalizar datos del dispositivo.
declare(strict_types=1);

// Comentario: Validar el contrato de telemetría antes de cualquier escritura.
final class TelemetryValidator
{
    // Comentario: Normalizar alias históricos conservando los once estados documentados.
    public static function normalize(array $payload): array
    {
        // Comentario: Rechazar revisiones explícitas desconocidas; ausencia identifica el contrato legado.
        if (isset($payload['protocol_version']) && $payload['protocol_version'] !== '1.1') {
            JsonResponse::error('protocolo_incompatible', 'Versión de protocolo no soportada.', 422);
        }

        // Comentario: Exigir un estado textual sin inventar OFF ante datos incompletos.
        $state = strtoupper(Validation::requiredString($payload, 'state', 'El estado es obligatorio.', 10));
        // Comentario: Aceptar CHECK como alias legado y persistir CHC como estado canónico.
        $payload['state'] = $state === 'CHECK' ? 'CHC' : $state;
        // Comentario: Comprobar el catálogo completo incluyendo recuperación de encendido.
        Validation::allowedString($payload, 'state', ['OFF', 'CHC', 'ACC', 'STB', 'REC', 'NORMAL', 'MOD', 'MAN', 'SIC', 'SPE', 'ALT'], 'Estado de caldera no permitido.');

        // Comentario: Mantener modo manual como valor legado cuando no se informa.
        $payload['mode'] = $payload['mode'] ?? 'manual';
        // Comentario: Impedir valores que incumplen el ENUM de MySQL.
        Validation::allowedString($payload, 'mode', ['manual', 'automatico', 'mantenimiento', 'seguridad'], 'Modo no permitido.');

        // Comentario: Reconciliar nombres anteriores sin aceptar valores contradictorios.
        foreach (['temp_water' => 'water_temp', 'temp_smoke' => 'smoke_temp', 'pellet_level' => 'fuel_level'] as $alias => $canonical) {
            // Comentario: Procesar únicamente alias presentes en la muestra.
            if (array_key_exists($alias, $payload)) {
                // Comentario: Rechazar duplicados que difieran para evitar interpretaciones por capa.
                if (array_key_exists($canonical, $payload) && $payload[$canonical] !== $payload[$alias]) {
                    JsonResponse::error('telemetria_ambigua', 'Los alias de telemetría difieren.', 422);
                }
                // Comentario: Trasladar el dato al nombre canónico.
                $payload[$canonical] = $payload[$alias];
                // Comentario: Retirar el alias tras su normalización.
                unset($payload[$alias]);
            }
        }

        // Comentario: Aplicar límites de transporte del proyecto, ajenos a consignas del fabricante.
        foreach (['water_temp' => [-40, 125], 'smoke_temp' => [-40, 600], 'fuel_level' => [0, 100], 'connectivity_signal_pct' => [0, 100]] as $field => [$min, $max]) {
            // Comentario: Permitir ausencia de medidas opcionales sin fabricar valores.
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $payload[$field] = Validation::decimalRange($payload, $field, $min, $max, 'Medida fuera de rango.');
            }
        }

        // Comentario: Rechazar una estructura de salidas distinta de objeto o array asociativo.
        if (isset($payload['outputs']) && !is_array($payload['outputs'])) {
            JsonResponse::error('validacion_error', 'outputs debe ser un objeto.', 422);
        }

        // Comentario: Unificar salidas anidadas y planas sin usar la conversión permisiva empty().
        foreach (['auger', 'igniter', 'pump', 'fan_primary_pct', 'fan_secondary_pct'] as $field) {
            // Comentario: Detectar salidas enviadas en el formato anidado legado.
            if (array_key_exists($field, $payload['outputs'] ?? [])) {
                // Comentario: Evitar que las dos representaciones expresen estados incompatibles.
                if (array_key_exists($field, $payload) && $payload[$field] !== $payload['outputs'][$field]) {
                    JsonResponse::error('telemetria_ambigua', 'Las salidas duplicadas difieren.', 422);
                }
                // Comentario: Usar siempre la representación plana validada.
                $payload[$field] = $payload['outputs'][$field];
            }

            // Comentario: Omitir salidas no informadas para conservar compatibilidad.
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            // Comentario: Validar porcentajes enteros y estados booleanos sin aceptar textos como false.
            if (str_ends_with($field, '_pct')) {
                $number = Validation::decimalRange($payload, $field, 0, 100, 'Porcentaje fuera de rango.');
                // Comentario: Rechazar fracciones que se perderían en las columnas enteras.
                if (floor($number) !== $number) {
                    JsonResponse::error('validacion_error', 'El porcentaje debe ser entero.', 422);
                }
                // Comentario: Normalizar el porcentaje validado.
                $payload[$field] = (int) $number;
            } elseif (!in_array($payload[$field], [true, false, 0, 1], true)) {
                JsonResponse::error('validacion_error', 'La salida debe ser booleana.', 422);
            }
        }

        // Comentario: El repositorio recibe únicamente salidas canónicas ya verificadas.
        unset($payload['outputs']);
        // Comentario: Devolver el payload validado conservando metadatos adicionales del firmware.
        return $payload;
    }
}
