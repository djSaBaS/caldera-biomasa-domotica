# Versión del proyecto

## Versión actual

**0.3.1-sprint-02-hardening**

## Fecha

2026-05-13

## Estado

Base de desarrollo con autenticación PHP real preparada, persistencia MySQL opcional, modo degradado seguro y hardening de lectura serie/entrada HTTP.

## Incluye

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
- Añadir tests unitarios e integración con base de datos efímera.
- Validar sensores reales en banco.
- Revisar la lógica original contra manuales antes de activar hardware.
- Definir inventario final de sensores.
