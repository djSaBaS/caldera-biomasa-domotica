<?php

// Comentario: Declarar tipos estrictos para validación fiable de parámetros.
declare(strict_types=1);

// Comentario: Definir catálogo de parámetros permitidos para configuración de caldera.
final class BoilerConfigValidator
{
    // Comentario: Evitar instancias porque la validación usa catálogo estático.
    private function __construct()
    {
    }

    // Comentario: Obtener catálogo completo de parámetros con límites y explicación.
    public static function catalog(): array
    {
        // Comentario: Devolver metadatos usados por backend, frontend y documentación.
        return [
            'mode' => ['label' => 'Modo', 'unit' => '', 'min' => null, 'max' => null, 'allowed' => ['manual', 'automatico', 'mantenimiento'], 'description' => 'Modo operativo solicitado, siempre validado por firmware.'],
            'auger_cycle_seconds' => ['label' => 'Ciclo sinfín', 'unit' => 's', 'min' => 2, 'max' => 120, 'allowed' => null, 'description' => 'Tiempo ON y OFF del sinfín; ambos deben ser iguales.'],
            'fan_primary_pct' => ['label' => 'Ventilador primario', 'unit' => '%', 'min' => 0, 'max' => 100, 'allowed' => null, 'description' => 'Porcentaje simulado del ventilador primario.'],
            'fan_secondary_pct' => ['label' => 'Ventilador secundario', 'unit' => '%', 'min' => 0, 'max' => 100, 'allowed' => null, 'description' => 'Porcentaje simulado del ventilador secundario.'],
            'pump_on_temp' => ['label' => 'Activación bomba', 'unit' => '°C', 'min' => 35, 'max' => 75, 'allowed' => null, 'description' => 'Temperatura de agua a partir de la cual se permite bomba.'],
            'target_temp' => ['label' => 'Temperatura objetivo', 'unit' => '°C', 'min' => 55, 'max' => 85, 'allowed' => null, 'description' => 'Temperatura objetivo de trabajo normal.'],
            'maintenance_temp' => ['label' => 'Temperatura mantenimiento', 'unit' => '°C', 'min' => 60, 'max' => 88, 'allowed' => null, 'description' => 'Umbral para mantenimiento o modulación.'],
            'safety_temp' => ['label' => 'Temperatura seguridad', 'unit' => '°C', 'min' => 75, 'max' => 95, 'allowed' => null, 'description' => 'Límite de seguridad que detiene aporte de combustible.'],
            'startup_timeout_seconds' => ['label' => 'Timeout encendido', 'unit' => 's', 'min' => 60, 'max' => 2400, 'allowed' => null, 'description' => 'Tiempo máximo permitido para fase de encendido.'],
            'post_ventilation_seconds' => ['label' => 'Post-ventilación', 'unit' => 's', 'min' => 30, 'max' => 1800, 'allowed' => null, 'description' => 'Tiempo de ventilación segura tras apagado.'],
            'telemetry_interval_seconds' => ['label' => 'Intervalo telemetría', 'unit' => 's', 'min' => 5, 'max' => 300, 'allowed' => null, 'description' => 'Frecuencia de envío de telemetría.'],
            'config_poll_interval_seconds' => ['label' => 'Consulta configuración', 'unit' => 's', 'min' => 10, 'max' => 600, 'allowed' => null, 'description' => 'Frecuencia de consulta de configuración remota.'],
            'notifications_enabled' => ['label' => 'Notificaciones', 'unit' => '', 'min' => null, 'max' => null, 'allowed' => [0, 1], 'description' => 'Activa o desactiva avisos del sistema.'],
        ];
    }

    // Comentario: Obtener configuración segura de respaldo cuando MySQL no está disponible.
    public static function defaultConfig(string $deviceUid): array
    {
        // Comentario: Devolver parámetros conservadores con sinfín ON igual a OFF.
        return [
            'config_version' => 1,
            'device_id' => $deviceUid,
            'mode' => 'manual',
            'auger_cycle_seconds' => 10,
            'fan_primary_pct' => 50,
            'fan_secondary_pct' => 50,
            'pump_on_temp' => 60,
            'target_temp' => 75,
            'maintenance_temp' => 80,
            'safety_temp' => 90,
            'startup_timeout_seconds' => 900,
            'post_ventilation_seconds' => 180,
            'telemetry_interval_seconds' => 10,
            'config_poll_interval_seconds' => 30,
            'notifications_enabled' => 1,
        ];
    }
}
