# Versión del proyecto

## Versión actual

**0.4.5-dashboard-api-frontend**

## Fecha

2026-05-15

## Estado

Base de desarrollo con autenticación PHP, administración web segura inicial, dashboard API, frontend sincronizado, rate limiting básico, seed demo, tests, CI con Arduino CLI, firmware con puente ESP32 y cache offline EEPROM, diagramas de conexión documentados, persistencia MySQL opcional, modo degradado seguro y simulación activa.

## Incluye

- Dashboard API con KPIs agregados, fallback seguro y frontend sincronizado.

- CI con Arduino CLI para compilar firmware Arduino Mega 2560 y ESP32.
- Diagramas SVG de conexión UART real y advertencia de accesorios con pinout pendiente.

- Firmware Arduino/ESP32 con protocolo `TEL`/`CFG`/`ACK`, envío de telemetría al backend y cache EEPROM de configuración válida.
- Validación estática del contrato firmware integrada en checks locales y CI.

- Seed demo SQL para previsualización con datos ficticios de telemetría, eventos, combustible y mantenimiento.
- Puerta de calidad local y workflow CI para lint, pruebas unitarias, validación de seed y smoke API.

- Rate limiting básico en login, restablecimiento de contraseña y endpoints de dispositivo.

- Administración web segura inicial: CSRF, autorización por roles, usuarios, dispositivos y solicitud auditable de comandos.

- Hardening Sprint 02.1: lectura serie ESP32 no bloqueante, `php://input` cacheado y conexión PDO con error seguro.
- Rechazo de placeholders públicos como API key de dispositivo.

- Documento Sprint 02.
- Autenticación con sesiones PHP contra tabla `users`.
- Solicitud de restablecimiento de contraseña con token hasheado.
- Validación de API key por variable de entorno o `devices.api_key_hash`.
- Persistencia MySQL para telemetría, eventos, combustible y mantenimiento cuando existe base importada.
- Lectura de configuración y comandos desde MySQL con fallback seguro.
- Repositorios PHP para separar acceso a datos.
- Validaciones centralizadas de entrada.
- Carga local de `server/.env` sin dependencias externas.
- `.gitignore` para evitar subir secretos locales.
- Panel web conectado a endpoints reales de login y restablecimiento.
- Script CLI para generar hashes de contraseña.
- Prueba de humo backend reproducible.

## Pendiente

- Crear autorización fina por rol en cada endpoint.
- Añadir CSRF para formularios web autenticados.
- Implementar alta/edición real de usuarios desde panel.
- Crear migraciones versionadas en lugar de un único `schema.sql`.
- Ampliar cobertura de tests unitarios e incorporar integración con base de datos efímera.
- Validar sensores reales en banco.
- Revisar la lógica original contra manuales antes de activar hardware.
- Definir inventario final de sensores.
