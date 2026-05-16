<?php

// Comentario: Declarar tipos estrictos para construir snapshots consistentes del panel.
declare(strict_types=1);

// Comentario: Centralizar datos agregados del dashboard para API y frontend.
final class DashboardRepository
{
    // Comentario: Evitar instancias porque el repositorio expone operaciones estáticas.
    private function __construct()
    {
    }

    // Comentario: Obtener snapshot seguro desde MySQL o fallback si faltan tablas.
    public static function snapshot(?PDO $connection, string $deviceUid): array
    {
        // Comentario: Preparar snapshot base para modo degradado y demo.
        $snapshot = self::fallbackSnapshot($deviceUid);

        // Comentario: Devolver fallback si no existe conexión real.
        if (!$connection instanceof PDO) {
            return $snapshot;
        }

        // Comentario: Proteger el endpoint ante bases parcialmente migradas.
        try {
            // Comentario: Obtener última telemetría disponible para el dispositivo.
            $telemetry = self::latestTelemetry($connection, $deviceUid);

            // Comentario: Enriquecer KPIs con telemetría real si existe.
            if (is_array($telemetry)) {
                $snapshot = self::applyTelemetry($snapshot, $telemetry);
            }

            // Comentario: Obtener resumen de combustible persistido.
            $fuel = FuelRepository::summary($connection);

            // Comentario: Enriquecer KPIs y sección combustible.
            $snapshot = self::applyFuelSummary($snapshot, $fuel);

            // Comentario: Obtener últimos eventos para la sección de logs.
            $snapshot['sections']['logs']['items'] = self::latestEvents($connection, $deviceUid);

            // Comentario: Obtener últimos mantenimientos para la sección correspondiente.
            $snapshot['sections']['mantenimiento']['items'] = self::latestMaintenance($connection);

            // Comentario: Marcar origen de datos como base de datos.
            $snapshot['source'] = 'database';
        } catch (PDOException) {
            // Comentario: Mantener fallback si una tabla aún no existe o falla una consulta.
            $snapshot['source'] = 'fallback_safe';
        }

        // Comentario: Devolver snapshot final normalizado.
        return $snapshot;
    }

    // Comentario: Construir snapshot fallback estable para desarrollo sin MySQL.
    public static function fallbackSnapshot(string $deviceUid = 'caldera-01'): array
    {
        // Comentario: Devolver datos demo conservadores y explícitamente simulados.
        return [
            'device_id' => $deviceUid !== '' ? $deviceUid : 'caldera-01',
            'source' => 'fallback_safe',
            'generated_at' => gmdate('c'),
            'kpis' => [
                ['label' => 'Estado', 'value' => 'NORMAL', 'icon' => 'bi-fire', 'color' => 'success'],
                ['label' => 'Agua', 'value' => '72.5 °C', 'icon' => 'bi-thermometer-half', 'color' => 'info'],
                ['label' => 'Humos', 'value' => '214 °C', 'icon' => 'bi-wind', 'color' => 'warning'],
                ['label' => 'Combustible', 'value' => '78 %', 'icon' => 'bi-fuel-pump', 'color' => 'success'],
                ['label' => 'Gasto hoy', 'value' => '18.4 kg', 'icon' => 'bi-basket', 'color' => 'secondary'],
                ['label' => 'Gasto mes', 'value' => '426 kg', 'icon' => 'bi-calendar3', 'color' => 'secondary'],
                ['label' => 'Coste diario', 'value' => '7.36 €', 'icon' => 'bi-currency-euro', 'color' => 'warning'],
                ['label' => 'Coste mensual', 'value' => '170.40 €', 'icon' => 'bi-graph-up', 'color' => 'warning'],
                ['label' => 'Horas', 'value' => '6.8 h', 'icon' => 'bi-clock-history', 'color' => 'info'],
                ['label' => 'Última alarma', 'value' => 'Sin alarma', 'icon' => 'bi-shield-check', 'color' => 'success'],
                ['label' => 'Comunicación', 'value' => 'modo demo', 'icon' => 'bi-wifi', 'color' => 'secondary'],
                ['label' => 'Modo', 'value' => 'Automático', 'icon' => 'bi-cpu', 'color' => 'primary'],
            ],
            'sections' => [
                'estado' => ['items' => ['Fase actual: NORMAL', 'Relés activos: bomba simulada', 'Sinfín: inactivo', 'Bujía: inactiva', 'Ventilador primario: 58 %', 'Ventilador secundario: 42 %', 'Modo: automático', 'Señal WiFi: demo']],
                'configuracion' => ['items' => ['Ciclo sinfín: 10 s ON = 10 s OFF', 'Bomba: 60 °C', 'Objetivo: 75 °C', 'Seguridad: 90 °C', 'Telemetría: cada 10 s']],
                'logs' => ['items' => ['info: panel en modo demo', 'aviso: sin MySQL o sin telemetría real', 'error: sin incidencias reales', 'critico: ninguno']],
                'combustible' => ['items' => ['Stock estimado: 420 kg', 'Consumo diario: 18.4 kg', 'Consumo mensual: 426 kg', 'Coste mensual: 170.40 €']],
                'mantenimiento' => ['items' => ['Próxima limpieza: 2026-05-20', 'Horas desde revisión: 124 h', 'Kg desde limpieza: 210 kg', 'Adjuntos: fase futura']],
            ],
        ];
    }

    // Comentario: Consultar última telemetría del dispositivo solicitado o global.
    private static function latestTelemetry(PDO $connection, string $deviceUid): ?array
    {
        // Comentario: Preparar consulta filtrada si se recibe identificador de dispositivo.
        if ($deviceUid !== '') {
            $statement = $connection->prepare('SELECT telemetry.*, devices.device_uid FROM telemetry INNER JOIN devices ON devices.id = telemetry.device_id WHERE devices.device_uid = :device_uid ORDER BY telemetry.recorded_at DESC, telemetry.id DESC LIMIT 1');

            // Comentario: Ejecutar consulta parametrizada por UID.
            $statement->execute(['device_uid' => $deviceUid]);
        } else {
            // Comentario: Preparar consulta global para la última muestra disponible.
            $statement = $connection->query('SELECT telemetry.*, devices.device_uid FROM telemetry INNER JOIN devices ON devices.id = telemetry.device_id ORDER BY telemetry.recorded_at DESC, telemetry.id DESC LIMIT 1');
        }

        // Comentario: Obtener fila asociativa si existe.
        $row = $statement->fetch();

        // Comentario: Devolver nulo si no hay telemetría.
        if (!is_array($row)) {
            return null;
        }

        // Comentario: Devolver fila normalizada como array.
        return $row;
    }

    // Comentario: Aplicar datos de telemetría a KPIs y sección de estado.
    private static function applyTelemetry(array $snapshot, array $telemetry): array
    {
        // Comentario: Reemplazar KPIs dependientes de telemetría.
        $snapshot['kpis'][0]['value'] = (string) ($telemetry['boiler_state'] ?? 'NORMAL');
        $snapshot['kpis'][1]['value'] = self::formatNumber($telemetry['water_temp'] ?? null, ' °C');
        $snapshot['kpis'][2]['value'] = self::formatNumber($telemetry['smoke_temp'] ?? null, ' °C');
        $snapshot['kpis'][3]['value'] = self::formatNumber($telemetry['fuel_level_pct'] ?? null, ' %');
        $snapshot['kpis'][10]['value'] = (string) ($telemetry['recorded_at'] ?? 'sin fecha');
        $snapshot['kpis'][11]['value'] = (string) ($telemetry['mode'] ?? 'manual');

        // Comentario: Construir elementos de estado desde campos reales.
        $snapshot['sections']['estado']['items'] = [
            'Fase actual: ' . (string) ($telemetry['boiler_state'] ?? 'desconocida'),
            'Bomba: ' . self::formatBoolean($telemetry['pump_active'] ?? 0),
            'Sinfín: ' . self::formatBoolean($telemetry['auger_active'] ?? 0),
            'Bujía: ' . self::formatBoolean($telemetry['igniter_active'] ?? 0),
            'Ventilador primario: ' . (int) ($telemetry['fan_primary_pct'] ?? 0) . ' %',
            'Ventilador secundario: ' . (int) ($telemetry['fan_secondary_pct'] ?? 0) . ' %',
            'Modo: ' . (string) ($telemetry['mode'] ?? 'manual'),
            'Última comunicación: ' . (string) ($telemetry['recorded_at'] ?? 'sin fecha'),
        ];

        // Comentario: Devolver snapshot actualizado.
        return $snapshot;
    }

    // Comentario: Aplicar resumen de combustible a KPIs y sección.
    private static function applyFuelSummary(array $snapshot, array $fuel): array
    {
        // Comentario: Actualizar KPI de gasto mensual con consumo agregado.
        $snapshot['kpis'][5]['value'] = number_format((float) ($fuel['kg_consumidos'] ?? 0), 2, ',', '.') . ' kg';

        // Comentario: Actualizar KPI de coste mensual con coste agregado.
        $snapshot['kpis'][7]['value'] = number_format((float) ($fuel['coste_estimado_eur'] ?? 0), 2, ',', '.') . ' €';

        // Comentario: Construir sección combustible desde resumen persistido.
        $snapshot['sections']['combustible']['items'] = [
            'Stock estimado: ' . number_format((float) ($fuel['stock_estimado_kg'] ?? 0), 2, ',', '.') . ' kg',
            'Kg comprados: ' . number_format((float) ($fuel['kg_comprados'] ?? 0), 2, ',', '.') . ' kg',
            'Kg consumidos: ' . number_format((float) ($fuel['kg_consumidos'] ?? 0), 2, ',', '.') . ' kg',
            'Coste estimado: ' . number_format((float) ($fuel['coste_estimado_eur'] ?? 0), 2, ',', '.') . ' €',
        ];

        // Comentario: Devolver snapshot actualizado.
        return $snapshot;
    }

    // Comentario: Consultar últimos eventos del sistema para panel.
    private static function latestEvents(PDO $connection, string $deviceUid): array
    {
        // Comentario: Preparar consulta filtrada por dispositivo si procede.
        if ($deviceUid !== '') {
            $statement = $connection->prepare('SELECT events.severity, events.title, events.created_at FROM events LEFT JOIN devices ON devices.id = events.device_id WHERE devices.device_uid = :device_uid OR events.device_id IS NULL ORDER BY events.created_at DESC, events.id DESC LIMIT 8');

            // Comentario: Ejecutar consulta parametrizada.
            $statement->execute(['device_uid' => $deviceUid]);
        } else {
            // Comentario: Consultar últimos eventos globales.
            $statement = $connection->query('SELECT severity, title, created_at FROM events ORDER BY created_at DESC, id DESC LIMIT 8');
        }

        // Comentario: Obtener filas disponibles.
        $rows = $statement->fetchAll() ?: [];

        // Comentario: Devolver mensaje fallback si no hay eventos.
        if ($rows === []) {
            return ['Sin eventos persistidos todavía.'];
        }

        // Comentario: Convertir filas a textos compactos de panel.
        return array_map(static fn (array $row): string => (string) $row['severity'] . ': ' . (string) $row['title'] . ' (' . (string) $row['created_at'] . ')', $rows);
    }

    // Comentario: Consultar últimos mantenimientos para panel.
    private static function latestMaintenance(PDO $connection): array
    {
        // Comentario: Obtener registros recientes mediante repositorio existente.
        $rows = MaintenanceRepository::latest($connection);

        // Comentario: Devolver mensaje fallback si no hay mantenimientos.
        if ($rows === []) {
            return ['Sin mantenimientos persistidos todavía.'];
        }

        // Comentario: Convertir mantenimientos a textos compactos.
        return array_map(static fn (array $row): string => (string) $row['maintenance_date'] . ' · ' . (string) $row['maintenance_type'] . ' · ' . (string) $row['description'], $rows);
    }

    // Comentario: Formatear número opcional con sufijo.
    private static function formatNumber(mixed $value, string $suffix): string
    {
        // Comentario: Devolver valor desconocido si no es numérico.
        if (!is_numeric($value)) {
            return 'N/D';
        }

        // Comentario: Formatear número con un decimal para KPIs.
        return number_format((float) $value, 1, ',', '.') . $suffix;
    }

    // Comentario: Formatear booleano almacenado como entero.
    private static function formatBoolean(mixed $value): string
    {
        // Comentario: Devolver texto activo para valores verdaderos.
        if ((int) $value === 1) {
            return 'activa';
        }

        // Comentario: Devolver texto inactivo para cualquier otro valor.
        return 'inactiva';
    }
}
