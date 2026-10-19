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
        $catalog = [
            'mode' => ['label' => 'Modo', 'unit' => '', 'min' => null, 'max' => null, 'allowed' => ['manual', 'automatico', 'mantenimiento'], 'description' => 'Modo almacenado; aplicación pendiente de la máquina original.'],
            'auger_cycle_seconds' => ['label' => 'Ciclo sinfín', 'unit' => 's', 'min' => 2, 'max' => 120, 'allowed' => null, 'description' => 'Preferencia simétrica heredada del proyecto; cada fase puede usar tiempos independientes.'],
            'fan_primary_pct' => ['label' => 'Ventilador primario', 'unit' => '%', 'min' => 0, 'max' => 100, 'allowed' => null, 'description' => 'Porcentaje simulado del ventilador primario.'],
            'fan_secondary_pct' => ['label' => 'Ventilador secundario', 'unit' => '%', 'min' => 0, 'max' => 100, 'allowed' => null, 'description' => 'Porcentaje simulado del ventilador secundario.'],
            'pump_on_temp' => ['label' => 'Activación bomba', 'unit' => '°C', 'min' => 35, 'max' => 75, 'allowed' => null, 'description' => 'Temperatura de agua a partir de la cual se permite bomba.'],
            'target_temp' => ['label' => 'Temperatura objetivo', 'unit' => '°C', 'min' => 55, 'max' => 85, 'allowed' => null, 'description' => 'Temperatura objetivo de trabajo normal.'],
            'maintenance_temp' => ['label' => 'Temperatura mantenimiento', 'unit' => '°C', 'min' => 60, 'max' => 88, 'allowed' => null, 'description' => 'Umbral para mantenimiento o modulación.'],
            'safety_temp' => ['label' => 'Temperatura seguridad', 'unit' => '°C', 'min' => 75, 'max' => 95, 'allowed' => null, 'description' => 'Límite de seguridad que detiene aporte de combustible.'],
            'startup_timeout_seconds' => ['label' => 'Timeout encendido', 'unit' => 's', 'min' => 60, 'max' => 2400, 'allowed' => null, 'description' => 'Timeout almacenado; aún no aplicado por el firmware de demostración.'],
            'post_ventilation_seconds' => ['label' => 'Post-ventilación', 'unit' => 's', 'min' => 30, 'max' => 1800, 'allowed' => null, 'description' => 'Postventilación almacenada; secuencia original aún no implementada.'],
            'telemetry_interval_seconds' => ['label' => 'Intervalo telemetría', 'unit' => 's', 'min' => 5, 'max' => 300, 'allowed' => null, 'description' => 'Frecuencia de envío de telemetría.'],
            'config_poll_interval_seconds' => ['label' => 'Consulta configuración', 'unit' => 's', 'min' => 10, 'max' => 600, 'allowed' => null, 'description' => 'Frecuencia de consulta de configuración remota.'],
            'notifications_enabled' => ['label' => 'Notificaciones', 'unit' => '', 'min' => null, 'max' => null, 'allowed' => [0, 1], 'description' => 'Preferencia almacenada; envío de avisos aún no implementado.'],
        ];
        // Comentario: Exponer también los perfiles originales de sinfín con sus unidades diferenciadas.
        foreach (array_slice(self::wireFields(), 11, null, true) as $wire => $field) {
            // Comentario: Añadir solo campos de fase que no estaban en el catálogo legado.
            if (!isset($catalog[$field])) {
                $catalog[$field] = ['label' => $wire, 'unit' => $wire === 'man_pause' ? 'min' : 's', 'min' => $wire === 'man_pause' ? 1 : 0, 'max' => $wire === 'man_work' ? 900 : 300, 'allowed' => null, 'description' => 'Perfil de sinfín por fase; MAN requiere T05 = CP09 + CL09.'];
            }
        }
        // Comentario: Devolver el catálogo ampliado sin alterar nombres de campos existentes.
        return $catalog;
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
    // Comentario: Mapear claves HTTP con unidades a claves UART compactas compartidas con Mega.
    public static function wireFields(): array
    {
        // Comentario: Mantener el mismo orden que CONFIG_KEYS en ControlModel.h.
        return ['version' => 'config_version', 'auger' => 'auger_cycle_seconds', 'fan1' => 'fan_primary_pct', 'fan2' => 'fan_secondary_pct', 'pump' => 'pump_on_temp', 'target' => 'target_temp', 'maintenance' => 'maintenance_temp', 'safety' => 'safety_temp', 'telemetry' => 'telemetry_interval_seconds', 'poll' => 'config_poll_interval_seconds', 'notifications' => 'notifications_enabled', 'acc_on' => 'acc_on_seconds', 'acc_off' => 'acc_off_seconds', 'stb_on' => 'stb_on_seconds', 'stb_off' => 'stb_off_seconds', 'normal_on' => 'normal_on_seconds', 'normal_off' => 'normal_off_seconds', 'mod_on' => 'mod_on_seconds', 'mod_off' => 'mod_off_seconds', 'man_on' => 'man_on_seconds', 'man_off' => 'man_off_seconds', 'man_pause' => 'man_pause_minutes', 'man_work' => 'man_work_seconds'];
    }

    // Comentario: Exportar únicamente enteros representables y perfiles coherentes para firmware.
    public static function normalize(array $config): array
    {
        // Comentario: Compartir límites del contrato de simulación, sin presentarlos como valores de fábrica.
        $minimum = [1, 2, 0, 0, 35, 55, 60, 75, 5, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0];
        // Comentario: Acotar explícitamente la versión a los 32 bits usados en ambos microcontroladores.
        $maximum = [4294967295, 120, 100, 100, 75, 85, 88, 95, 300, 600, 1, 300, 300, 300, 300, 300, 300, 300, 300, 300, 300, 300, 900];
        // Comentario: Completar el legado simétrico solo cuando no se ha configurado un par por fase.
        foreach (['acc', 'stb', 'normal', 'mod', 'man'] as $phase) {
            // Comentario: Rechazar perfiles parciales para no mezclar una pausa nueva con una carga heredada.
            if (isset($config[$phase . '_on_seconds']) !== isset($config[$phase . '_off_seconds'])) {
                JsonResponse::error('configuracion_invalida', 'Cada fase requiere trabajo y pausa juntos.', 422);
            }
            // Comentario: Derivar el legado simétrico excepto MAN, que queda sin carga por defecto.
            $config[$phase . '_on_seconds'] ??= $phase === 'man' ? 0 : $config['auger_cycle_seconds'];
            $config[$phase . '_off_seconds'] ??= $phase === 'man' ? 0 : $config['auger_cycle_seconds'];
        }
        // Comentario: Mantener MAN deshabilitado salvo ventana explícita válida.
        $config['man_pause_minutes'] ??= 1;
        $config['man_work_seconds'] ??= 0;
        // Comentario: Convertir los decimales enteros devueltos por PDO sin truncar fracciones.
        foreach (array_values(self::wireFields()) as $index => $field) {
            $number = Validation::decimalRange($config, $field, $minimum[$index], $maximum[$index], 'Configuración fuera del contrato de firmware.');
            // Comentario: Exigir enteros porque el controlador actual no representa décimas de consigna.
            if (floor($number) !== $number) {
                JsonResponse::error('configuracion_invalida', 'El firmware requiere consignas enteras.', 422, ['field' => $field]);
            }
            // Comentario: Emitir números JSON, evitando cadenas numéricas dependientes del driver PDO.
            $config[$field] = (int) $number;
        }
        // Comentario: Aplicar el mismo orden térmico y ventana MAN que comprueba Mega.
        if ($config['pump_on_temp'] >= $config['target_temp'] || $config['target_temp'] > $config['maintenance_temp'] || $config['maintenance_temp'] >= $config['safety_temp'] || $config['man_work_seconds'] !== $config['man_on_seconds'] + $config['man_off_seconds']) {
            JsonResponse::error('configuracion_invalida', 'Las relaciones térmicas o T05 = CP09 + CL09 no se cumplen.', 422);
        }
        // Comentario: Devolver el candidato íntegro tras validar todos los campos enviados a Mega.
        return $config;
    }
}
