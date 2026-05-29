-- Comentario: Crear base de datos opcional para entornos de desarrollo local.
CREATE DATABASE IF NOT EXISTS caldera_biomasa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Comentario: Seleccionar la base de datos del proyecto.
USE caldera_biomasa;

-- Comentario: Tabla de roles permitidos para usuarios del panel.
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Roles funcionales del panel web';

-- Comentario: Usuarios del sistema con contraseña hasheada, nunca en claro.
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
    password_reset_token_hash VARCHAR(255) NULL,
    password_reset_expires_at DATETIME NULL,
    last_access_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuarios autorizados para operar o consultar la caldera';

-- Comentario: Dispositivos físicos o simulados registrados en backend.
CREATE TABLE IF NOT EXISTS devices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_uid VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    api_key_hash VARCHAR(255) NOT NULL,
    location VARCHAR(160) NULL,
    firmware_arduino VARCHAR(40) NULL,
    firmware_esp32 VARCHAR(40) NULL,
    status ENUM('activo','inactivo','mantenimiento') NOT NULL DEFAULT 'activo',
    last_seen_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_devices_status (status),
    INDEX idx_devices_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dispositivos Arduino/ESP32 autorizados';

-- Comentario: Histórico de telemetría recibida desde el dispositivo.
CREATE TABLE IF NOT EXISTS telemetry (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    boiler_state ENUM('OFF','CHECK','ACC','STB','NORMAL','MOD','MAN','SIC','SPE','ALT') NOT NULL,
    mode ENUM('manual','automatico','mantenimiento','seguridad') NOT NULL DEFAULT 'manual',
    water_temp DECIMAL(5,2) NULL,
    smoke_temp DECIMAL(6,2) NULL,
    fuel_level_pct DECIMAL(5,2) NULL,
    auger_active TINYINT(1) NOT NULL DEFAULT 0,
    igniter_active TINYINT(1) NOT NULL DEFAULT 0,
    pump_active TINYINT(1) NOT NULL DEFAULT 0,
    fan_primary_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
    fan_secondary_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
    connectivity_signal_pct TINYINT UNSIGNED NULL,
    payload_json JSON NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_telemetry_device FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_telemetry_device_recorded (device_id, recorded_at),
    INDEX idx_telemetry_state_recorded (boiler_state, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Medidas y estado periódico de la caldera';

-- Comentario: Configuración activa o pendiente de aplicar por dispositivo.
CREATE TABLE IF NOT EXISTS boiler_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    config_version INT UNSIGNED NOT NULL DEFAULT 1,
    mode ENUM('manual','automatico','mantenimiento') NOT NULL DEFAULT 'manual',
    auger_cycle_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    fan_primary_pct TINYINT UNSIGNED NOT NULL DEFAULT 50,
    fan_secondary_pct TINYINT UNSIGNED NOT NULL DEFAULT 50,
    pump_on_temp DECIMAL(5,2) NOT NULL DEFAULT 60.00,
    target_temp DECIMAL(5,2) NOT NULL DEFAULT 75.00,
    maintenance_temp DECIMAL(5,2) NOT NULL DEFAULT 80.00,
    safety_temp DECIMAL(5,2) NOT NULL DEFAULT 90.00,
    startup_timeout_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 900,
    post_ventilation_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 180,
    telemetry_interval_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    config_poll_interval_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
    pending_apply TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_boiler_config_device FOREIGN KEY (device_id) REFERENCES devices(id),
    UNIQUE KEY uq_boiler_config_device (device_id),
    CHECK (auger_cycle_seconds BETWEEN 2 AND 120),
    CHECK (fan_primary_pct <= 100),
    CHECK (fan_secondary_pct <= 100),
    CHECK (safety_temp >= target_temp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Parámetros configurables de caldera con límites seguros';

-- Comentario: Historial auditable de cambios de configuración.
CREATE TABLE IF NOT EXISTS config_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    parameter_key VARCHAR(80) NOT NULL,
    previous_value VARCHAR(120) NULL,
    new_value VARCHAR(120) NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_config_history_device FOREIGN KEY (device_id) REFERENCES devices(id),
    CONSTRAINT fk_config_history_user FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_config_history_device_created (device_id, created_at),
    INDEX idx_config_history_parameter (parameter_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cambios de parámetros trazables';

-- Comentario: Cola de comandos remotos solicitados por usuarios autorizados.
CREATE TABLE IF NOT EXISTS commands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NULL,
    command_type ENUM('START','STOP','RESET_ALARM','ENTER_MAINTENANCE','EXIT_MAINTENANCE') NOT NULL,
    status ENUM('pendiente','enviado','aceptado','rechazado','expirado') NOT NULL DEFAULT 'pendiente',
    payload_json JSON NULL,
    not_before_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_commands_device FOREIGN KEY (device_id) REFERENCES devices(id),
    CONSTRAINT fk_commands_user FOREIGN KEY (requested_by) REFERENCES users(id),
    INDEX idx_commands_device_status (device_id, status),
    INDEX idx_commands_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Órdenes remotas pendientes de validación por firmware';

-- Comentario: Historial de cambios de estado de comandos.
CREATE TABLE IF NOT EXISTS command_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    command_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pendiente','enviado','aceptado','rechazado','expirado') NOT NULL,
    message VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_command_history_command FOREIGN KEY (command_id) REFERENCES commands(id),
    INDEX idx_command_history_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Trazabilidad de órdenes remotas';

-- Comentario: Eventos generales de operación, cambios y comunicaciones.
CREATE TABLE IF NOT EXISTS events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NULL,
    event_type ENUM('estado','configuracion','orden','comunicacion','sensor','mantenimiento','sistema') NOT NULL,
    severity ENUM('info','aviso','error','critico') NOT NULL DEFAULT 'info',
    origin ENUM('firmware','backend','web','sistema') NOT NULL DEFAULT 'sistema',
    title VARCHAR(160) NOT NULL,
    message TEXT NULL,
    status ENUM('abierto','revisado','cerrado') NOT NULL DEFAULT 'abierto',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_device FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_events_device_created (device_id, created_at),
    INDEX idx_events_severity_created (severity, created_at),
    INDEX idx_events_type_status (event_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de eventos e incidencias';

-- Comentario: Alarmas específicas de seguridad o operación crítica.
CREATE TABLE IF NOT EXISTS alarms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    alarm_code VARCHAR(80) NOT NULL,
    severity ENUM('aviso','error','critico') NOT NULL DEFAULT 'error',
    message VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cleared_at DATETIME NULL,
    CONSTRAINT fk_alarms_device FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_alarms_device_active (device_id, active),
    INDEX idx_alarms_severity_started (severity, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alarmas de caldera y seguridad';

-- Comentario: Compras de combustible para cálculo de stock y coste.
CREATE TABLE IF NOT EXISTS fuel_purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fuel_type ENUM('pellet','hueso_aceituna','otro') NOT NULL,
    purchase_date DATE NOT NULL,
    kg_purchased DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    price_per_kg DECIMAL(10,4) GENERATED ALWAYS AS (total_price / NULLIF(kg_purchased, 0)) STORED,
    supplier VARCHAR(160) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fuel_purchases_date (purchase_date),
    INDEX idx_fuel_purchases_type (fuel_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Compras de pellet, hueso u otros combustibles';

-- Comentario: Consumo estimado diario de combustible por dispositivo.
CREATE TABLE IF NOT EXISTS fuel_consumption (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    consumption_date DATE NOT NULL,
    fuel_type ENUM('pellet','hueso_aceituna','otro') NOT NULL,
    kg_consumed DECIMAL(10,2) NOT NULL DEFAULT 0,
    estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    running_hours DECIMAL(8,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fuel_consumption_device FOREIGN KEY (device_id) REFERENCES devices(id),
    UNIQUE KEY uq_fuel_consumption_device_date_type (device_id, consumption_date, fuel_type),
    INDEX idx_fuel_consumption_date (consumption_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consumos estimados para informes y costes';

-- Comentario: Registros manuales de mantenimiento realizado.
CREATE TABLE IF NOT EXISTS maintenance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    maintenance_type ENUM('limpieza','revision','pieza','reparacion') NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    replaced_parts TEXT NULL,
    technician VARCHAR(160) NULL,
    next_review_date DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_records_device FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_maintenance_records_device_date (device_id, maintenance_date),
    INDEX idx_maintenance_records_type (maintenance_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de limpiezas, revisiones y reparaciones';

-- Comentario: Planificación de mantenimientos futuros.
CREATE TABLE IF NOT EXISTS maintenance_schedule (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    maintenance_type ENUM('limpieza','revision','pieza','reparacion') NOT NULL,
    due_date DATE NULL,
    due_running_hours DECIMAL(8,2) NULL,
    due_kg_consumed DECIMAL(10,2) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_schedule_device FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_maintenance_schedule_device_active (device_id, active),
    INDEX idx_maintenance_schedule_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alertas futuras de mantenimiento preventivo';

-- Comentario: Historial de notificaciones generadas por el sistema.
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NULL,
    channel ENUM('email','telegram','whatsapp') NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    subject VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pendiente','enviada','fallida','desactivada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    CONSTRAINT fk_notifications_device FOREIGN KEY (device_id) REFERENCES devices(id),
    INDEX idx_notifications_status_created (status, created_at),
    INDEX idx_notifications_channel (channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificaciones emitidas o pendientes';

-- Comentario: Destinatarios configurados para avisos por canal.
CREATE TABLE IF NOT EXISTS notification_recipients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel ENUM('email','telegram','whatsapp') NOT NULL,
    recipient VARCHAR(180) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    event_filter VARCHAR(160) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_notification_recipients_channel_enabled (channel, enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Destinatarios de notificaciones';

-- Comentario: Ajustes generales no secretos del sistema.
CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    public_visible TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ajustes generales de aplicación sin secretos';

-- Comentario: Insertar roles base sin duplicar si ya existen.
INSERT INTO roles (code, name, description) VALUES
('administrador', 'Administrador', 'Acceso completo a configuración, usuarios y operación.'),
('operador', 'Operador', 'Puede operar funciones autorizadas y revisar estados.'),
('solo_lectura', 'Solo lectura', 'Puede consultar estado e históricos sin modificar.'),
('mantenimiento', 'Mantenimiento', 'Puede registrar revisiones, limpiezas y reparaciones.')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);
