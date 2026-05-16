-- Comentario: Archivo idempotente de datos ficticios para previsualizar el panel en modo demo.
-- Comentario: No contiene credenciales reales y debe cargarse solo en entornos locales o de demostración.

-- Comentario: Seleccionar la base de datos del proyecto antes de insertar datos demo.
USE caldera_biomasa;

-- Comentario: Asegurar que los roles base existen aunque no se haya ejecutado completo el seed de desarrollo.
INSERT INTO roles (code, name, description) VALUES
('administrador', 'Administrador', 'Acceso completo a configuración, usuarios y operación.'),
('operador', 'Operador', 'Puede operar funciones autorizadas y revisar estados.'),
('solo_lectura', 'Solo lectura', 'Puede consultar estado e históricos sin modificar.'),
('mantenimiento', 'Mantenimiento', 'Puede registrar revisiones, limpiezas y reparaciones.')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- Comentario: Crear un usuario administrador ficticio para recorridos de demostración controlados.
INSERT INTO users (role_id, name, email, username, password_hash, status)
SELECT roles.id, 'Administradora Demo', 'demo.admin@example.test', 'demo_admin', '$2y$12$sOLbjJGV8ChLAzduMkZwuOkxCpU/S6RkxPMrGCHEGvti6ZSS20VbS', 'activo'
FROM roles WHERE roles.code = 'administrador'
ON DUPLICATE KEY UPDATE name = VALUES(name), role_id = VALUES(role_id), status = VALUES(status);

-- Comentario: Crear un dispositivo ficticio con hash de API key no productiva para telemetría demo.
INSERT INTO devices (device_uid, name, api_key_hash, location, firmware_arduino, firmware_esp32, status, last_seen_at)
VALUES ('caldera-demo-01', 'Caldera demo salón técnico', '$2y$12$uGEUrZWbeDMHCgIdGMXgaOyd3cMAMsXG7lOG.LIZYKliBNn.GNPYG', 'Sala técnica demo', 'mega-demo-0.4.2', 'esp32-demo-0.4.2', 'activo', DATE_SUB(NOW(), INTERVAL 2 MINUTE))
ON DUPLICATE KEY UPDATE name = VALUES(name), location = VALUES(location), firmware_arduino = VALUES(firmware_arduino), firmware_esp32 = VALUES(firmware_esp32), status = VALUES(status), last_seen_at = VALUES(last_seen_at);

-- Comentario: Crear configuración operativa ficticia para que la vista de parámetros tenga datos realistas.
INSERT INTO boiler_config (device_id, config_version, mode, auger_cycle_seconds, fan_primary_pct, fan_secondary_pct, pump_on_temp, target_temp, maintenance_temp, safety_temp, startup_timeout_seconds, post_ventilation_seconds, telemetry_interval_seconds, config_poll_interval_seconds, notifications_enabled, pending_apply)
SELECT devices.id, 2, 'automatico', 12, 58, 42, 58.00, 74.00, 81.00, 90.00, 900, 240, 15, 45, 1, 0
FROM devices WHERE devices.device_uid = 'caldera-demo-01'
ON DUPLICATE KEY UPDATE config_version = VALUES(config_version), mode = VALUES(mode), auger_cycle_seconds = VALUES(auger_cycle_seconds), fan_primary_pct = VALUES(fan_primary_pct), fan_secondary_pct = VALUES(fan_secondary_pct), pump_on_temp = VALUES(pump_on_temp), target_temp = VALUES(target_temp), maintenance_temp = VALUES(maintenance_temp), safety_temp = VALUES(safety_temp), pending_apply = VALUES(pending_apply);

-- Comentario: Insertar muestras de telemetría ficticia recientes sin duplicar filas si se recarga el seed.
INSERT INTO telemetry (device_id, boiler_state, mode, water_temp, smoke_temp, fuel_level_pct, auger_active, igniter_active, pump_active, fan_primary_pct, fan_secondary_pct, connectivity_signal_pct, payload_json, recorded_at)
SELECT devices.id, demo.boiler_state, demo.mode, demo.water_temp, demo.smoke_temp, demo.fuel_level_pct, demo.auger_active, demo.igniter_active, demo.pump_active, demo.fan_primary_pct, demo.fan_secondary_pct, demo.connectivity_signal_pct, JSON_OBJECT('demo', true, 'sample', demo.sample_code), DATE_SUB(NOW(), INTERVAL demo.minutes_ago MINUTE)
FROM devices
INNER JOIN (
    SELECT 'demo-telemetry-001' AS sample_code, 35 AS minutes_ago, 'ACC' AS boiler_state, 'automatico' AS mode, 48.20 AS water_temp, 96.50 AS smoke_temp, 82.00 AS fuel_level_pct, 1 AS auger_active, 1 AS igniter_active, 0 AS pump_active, 65 AS fan_primary_pct, 45 AS fan_secondary_pct, 88 AS connectivity_signal_pct
    UNION ALL SELECT 'demo-telemetry-002', 25, 'NORMAL', 'automatico', 63.40, 168.20, 79.50, 1, 0, 1, 58, 42, 91
    UNION ALL SELECT 'demo-telemetry-003', 15, 'NORMAL', 'automatico', 72.10, 206.80, 77.20, 0, 0, 1, 52, 38, 90
    UNION ALL SELECT 'demo-telemetry-004', 5, 'MOD', 'automatico', 78.60, 184.30, 75.90, 0, 0, 1, 35, 25, 89
) AS demo ON devices.device_uid = 'caldera-demo-01'
WHERE NOT EXISTS (
    SELECT 1 FROM telemetry existing
    WHERE existing.device_id = devices.id
    AND JSON_UNQUOTE(JSON_EXTRACT(existing.payload_json, '$.sample')) = demo.sample_code
);

-- Comentario: Insertar eventos ficticios para previsualizar avisos, actividad y trazabilidad del panel.
INSERT INTO events (device_id, event_type, severity, origin, title, message, status, created_at)
SELECT devices.id, demo.event_type, demo.severity, demo.origin, demo.title, demo.message, demo.status, DATE_SUB(NOW(), INTERVAL demo.minutes_ago MINUTE)
FROM devices
INNER JOIN (
    SELECT 40 AS minutes_ago, 'sistema' AS event_type, 'info' AS severity, 'backend' AS origin, 'Demo iniciada' AS title, 'Carga de datos ficticios para previsualización.' AS message, 'revisado' AS status
    UNION ALL SELECT 18, 'estado', 'info', 'firmware', 'Régimen estable', 'La caldera demo trabaja en estado NORMAL con bomba activa.', 'abierto'
    UNION ALL SELECT 7, 'mantenimiento', 'aviso', 'web', 'Limpieza próxima', 'Se recomienda revisar cenicero y cámara de combustión en la próxima parada.', 'abierto'
) AS demo ON devices.device_uid = 'caldera-demo-01'
WHERE NOT EXISTS (
    SELECT 1 FROM events existing
    WHERE existing.device_id = devices.id
    AND existing.title = demo.title
);

-- Comentario: Insertar un comando pendiente ficticio para probar la cola visible desde el panel.
INSERT INTO commands (device_id, requested_by, command_type, status, payload_json, not_before_at, expires_at)
SELECT devices.id, users.id, 'ENTER_MAINTENANCE', 'pendiente', JSON_OBJECT('demo', true, 'reason', 'Previsualización de cola de comandos'), NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE)
FROM devices
INNER JOIN users ON users.username = 'demo_admin'
WHERE devices.device_uid = 'caldera-demo-01'
AND NOT EXISTS (
    SELECT 1 FROM commands existing
    WHERE existing.device_id = devices.id
    AND existing.command_type = 'ENTER_MAINTENANCE'
    AND existing.status = 'pendiente'
);

-- Comentario: Insertar compras ficticias de combustible para mostrar costes y stock estimado.
INSERT INTO fuel_purchases (fuel_type, purchase_date, kg_purchased, total_price, supplier, notes)
SELECT demo.fuel_type, demo.purchase_date, demo.kg_purchased, demo.total_price, demo.supplier, demo.notes
FROM (
    SELECT 'pellet' AS fuel_type, DATE_SUB(CURDATE(), INTERVAL 20 DAY) AS purchase_date, 975.00 AS kg_purchased, 361.00 AS total_price, 'Proveedor demo biomasa' AS supplier, 'Saco estándar para pruebas visuales.' AS notes
    UNION ALL SELECT 'hueso_aceituna', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 650.00, 188.50, 'Cooperativa demo', 'Carga ficticia para comparar combustible.'
) AS demo
WHERE NOT EXISTS (
    SELECT 1 FROM fuel_purchases existing
    WHERE existing.fuel_type = demo.fuel_type
    AND existing.purchase_date = demo.purchase_date
    AND existing.supplier = demo.supplier
);

-- Comentario: Insertar consumos ficticios recientes para gráficas de consumo y coste.
INSERT INTO fuel_consumption (device_id, consumption_date, fuel_type, kg_consumed, estimated_cost, running_hours)
SELECT devices.id, demo.consumption_date, demo.fuel_type, demo.kg_consumed, demo.estimated_cost, demo.running_hours
FROM devices
INNER JOIN (
    SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS consumption_date, 'pellet' AS fuel_type, 18.40 AS kg_consumed, 6.81 AS estimated_cost, 9.50 AS running_hours
    UNION ALL SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pellet', 21.10, 7.81, 10.25
    UNION ALL SELECT CURDATE(), 'pellet', 8.30, 3.07, 4.00
) AS demo ON devices.device_uid = 'caldera-demo-01'
ON DUPLICATE KEY UPDATE kg_consumed = VALUES(kg_consumed), estimated_cost = VALUES(estimated_cost), running_hours = VALUES(running_hours);

-- Comentario: Insertar mantenimiento ficticio para previsualizar históricos y próximas revisiones.
INSERT INTO maintenance_records (device_id, maintenance_type, maintenance_date, description, cost, replaced_parts, technician, next_review_date)
SELECT devices.id, 'limpieza', DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Limpieza demo de intercambiador, brasero y cenicero.', 0.00, 'Sin sustituciones', 'Técnico demo', DATE_ADD(CURDATE(), INTERVAL 18 DAY)
FROM devices
WHERE devices.device_uid = 'caldera-demo-01'
AND NOT EXISTS (
    SELECT 1 FROM maintenance_records existing
    WHERE existing.device_id = devices.id
    AND existing.maintenance_date = DATE_SUB(CURDATE(), INTERVAL 12 DAY)
    AND existing.maintenance_type = 'limpieza'
);

-- Comentario: Insertar planificación preventiva ficticia para mostrar alertas futuras.
INSERT INTO maintenance_schedule (device_id, maintenance_type, due_date, due_running_hours, due_kg_consumed, active, notes)
SELECT devices.id, 'revision', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 120.00, 240.00, 1, 'Revisión demo programada tras un mes de uso simulado.'
FROM devices
WHERE devices.device_uid = 'caldera-demo-01'
AND NOT EXISTS (
    SELECT 1 FROM maintenance_schedule existing
    WHERE existing.device_id = devices.id
    AND existing.maintenance_type = 'revision'
    AND existing.active = 1
);

-- Comentario: Insertar destinatario ficticio desactivado para revisar configuración de notificaciones sin enviar avisos reales.
INSERT INTO notification_recipients (channel, recipient, enabled, event_filter)
SELECT 'email', 'demo.alertas@example.test', 0, 'critico,aviso'
WHERE NOT EXISTS (
    SELECT 1 FROM notification_recipients existing
    WHERE existing.channel = 'email'
    AND existing.recipient = 'demo.alertas@example.test'
);

-- Comentario: Insertar ajuste público ficticio que permite a la interfaz detectar modo demo.
INSERT INTO system_settings (setting_key, setting_value, public_visible)
VALUES ('demo_preview_enabled', '1', 1)
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), public_visible = VALUES(public_visible);
