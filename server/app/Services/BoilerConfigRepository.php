<?php

// Comentario: Declarar tipos estrictos para consulta de configuración.
declare(strict_types=1);

// Comentario: Encapsular lectura de configuración activa de caldera.
final class BoilerConfigRepository
{
    // Comentario: Evitar instancias de repositorio estático simple.
    private function __construct()
    {
    }

    // Comentario: Buscar configuración activa por dispositivo interno.
    public static function findByDevice(PDO $connection, int $deviceId): ?array
    {
        // Comentario: Preparar consulta parametrizada de configuración.
        $statement = $connection->prepare('SELECT config_version, mode, auger_cycle_seconds, fan_primary_pct, fan_secondary_pct, pump_on_temp, target_temp, maintenance_temp, safety_temp, startup_timeout_seconds, post_ventilation_seconds, telemetry_interval_seconds, config_poll_interval_seconds, notifications_enabled FROM boiler_config WHERE device_id = :device_id LIMIT 1');

        // Comentario: Ejecutar consulta con identificador interno.
        $statement->execute(['device_id' => $deviceId]);

        // Comentario: Obtener configuración si existe.
        $row = $statement->fetch();

        // Comentario: Devolver nulo si no hay configuración importada.
        if (!is_array($row)) {
            return null;
        }

        // Comentario: Devolver fila asociativa preparada para JSON.
        return $row;
    }
}
