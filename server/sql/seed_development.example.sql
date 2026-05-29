-- Comentario: Archivo de ejemplo para crear datos locales de desarrollo.
-- Comentario: No ejecutar en producción y no guardar aquí contraseñas reales.

-- Comentario: Seleccionar base de datos local del proyecto.
USE caldera_biomasa;

-- Comentario: Crear dispositivo local usando un hash de API key generado fuera de Git.
-- Comentario: Sustituir HASH_API_KEY_GENERADO por un hash creado con password_hash o herramienta equivalente.
INSERT INTO devices (device_uid, name, api_key_hash, location, status)
VALUES ('caldera-01', 'Caldera desarrollo', 'HASH_API_KEY_GENERADO', 'Banco de pruebas', 'activo')
ON DUPLICATE KEY UPDATE name = VALUES(name), location = VALUES(location), status = VALUES(status);

-- Comentario: Crear configuración segura inicial para el dispositivo local.
INSERT INTO boiler_config (device_id, config_version, mode, auger_cycle_seconds, fan_primary_pct, fan_secondary_pct, pump_on_temp, target_temp, maintenance_temp, safety_temp)
SELECT id, 1, 'manual', 10, 50, 50, 60.00, 75.00, 80.00, 90.00 FROM devices WHERE device_uid = 'caldera-01'
ON DUPLICATE KEY UPDATE config_version = VALUES(config_version), mode = VALUES(mode), auger_cycle_seconds = VALUES(auger_cycle_seconds);

-- Comentario: Crear usuario administrador local usando hash generado con tools/scripts/generar_hash_password.php.
-- Comentario: Sustituir HASH_PASSWORD_GENERADO por el resultado del script CLI.
INSERT INTO users (role_id, name, email, username, password_hash, status)
SELECT roles.id, 'Administrador local', 'admin@example.com', 'admin', 'HASH_PASSWORD_GENERADO', 'activo'
FROM roles WHERE roles.code = 'administrador'
ON DUPLICATE KEY UPDATE name = VALUES(name), role_id = VALUES(role_id), status = VALUES(status);
