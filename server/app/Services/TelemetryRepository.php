<?php

// Comentario: Declarar tipos estrictos para persistencia de telemetría.
declare(strict_types=1);

// Comentario: Encapsular escritura de telemetría en MySQL.
final class TelemetryRepository
{
    // Comentario: Evitar instancias de repositorio estático simple.
    private function __construct()
    {
    }

    // Comentario: Guardar una muestra de telemetría validada.
    public static function store(PDO $connection, int $deviceId, array $payload, string $state): int
    {
        // Comentario: Preparar inserción parametrizada de telemetría.
        $statement = $connection->prepare(
            'INSERT INTO telemetry (device_id, boiler_state, mode, water_temp, smoke_temp, fuel_level_pct, auger_active, igniter_active, pump_active, fan_primary_pct, fan_secondary_pct, connectivity_signal_pct, payload_json, recorded_at) VALUES (:device_id, :boiler_state, :mode, :water_temp, :smoke_temp, :fuel_level_pct, :auger_active, :igniter_active, :pump_active, :fan_primary_pct, :fan_secondary_pct, :connectivity_signal_pct, :payload_json, NOW())'
        );

        // Comentario: Extraer salidas anidadas si existen.
        $outputs = is_array($payload['outputs'] ?? null) ? $payload['outputs'] : [];

        // Comentario: Ejecutar inserción con conversiones explícitas.
        $statement->execute([
            'device_id' => $deviceId,
            'boiler_state' => $state,
            'mode' => (string) ($payload['mode'] ?? 'manual'),
            'water_temp' => self::nullableNumber($payload['water_temp'] ?? $payload['temp_water'] ?? null),
            'smoke_temp' => self::nullableNumber($payload['smoke_temp'] ?? $payload['temp_smoke'] ?? null),
            'fuel_level_pct' => self::nullableNumber($payload['fuel_level'] ?? $payload['pellet_level'] ?? null),
            'auger_active' => !empty($outputs['auger']) || !empty($payload['auger']) ? 1 : 0,
            'igniter_active' => !empty($outputs['igniter']) || !empty($payload['igniter']) ? 1 : 0,
            'pump_active' => !empty($outputs['pump']) || !empty($payload['pump']) ? 1 : 0,
            'fan_primary_pct' => (int) ($outputs['fan_primary_pct'] ?? $payload['fan_primary_pct'] ?? 0),
            'fan_secondary_pct' => (int) ($outputs['fan_secondary_pct'] ?? $payload['fan_secondary_pct'] ?? 0),
            'connectivity_signal_pct' => self::nullableNumber($payload['connectivity_signal_pct'] ?? null),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        // Comentario: Actualizar última comunicación del dispositivo tras persistir telemetría.
        DeviceRepository::touch($connection, $deviceId);

        // Comentario: Devolver identificador generado por MySQL.
        return (int) $connection->lastInsertId();
    }

    // Comentario: Convertir números opcionales a decimal o nulo.
    private static function nullableNumber(mixed $value): ?float
    {
        // Comentario: Devolver nulo para valores ausentes o no numéricos.
        if (!is_numeric($value)) {
            return null;
        }

        // Comentario: Convertir valor numérico a decimal.
        return (float) $value;
    }
}
