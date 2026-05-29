# Sprint 02 — Autenticación básica y persistencia segura

## Objetivo

Convertir la base simulada del Sprint 01 en una base de desarrollo más realista: autenticación PHP con sesiones, endpoints con persistencia MySQL cuando la base esté disponible, validaciones centralizadas y pruebas de humo reproducibles.

## Alcance

- Autenticación real contra tabla `users` con `password_hash` y `password_verify`.
- Solicitud de restablecimiento de contraseña sin revelar si el email existe.
- Persistencia opcional en MySQL para telemetría, eventos, combustible y mantenimiento.
- Lectura persistida de configuración y comandos si existe dispositivo registrado.
- Modo degradado seguro cuando MySQL no está disponible.
- Panel web preparado para llamar a endpoints reales de autenticación.
- Script de generación de hash de contraseña para crear usuarios de desarrollo.
- Pruebas de humo backend sin depender de hardware real.

## Orden de ejecución recomendado

1. Importar `server/sql/schema.sql` en MySQL local.
2. Generar un hash de contraseña con `tools/scripts/generar_hash_password.php`.
3. Crear usuario administrador local con el hash generado.
4. Registrar un dispositivo local con `api_key_hash` generado fuera de Git.
5. Configurar variables de entorno desde `server/.env.example`.
6. Levantar backend con `php -S localhost:8081 -t server`.
7. Levantar web con `php -S localhost:8080 -t web`.
8. Ejecutar `tests/backend/smoke_api.sh`.

## Criterios de aceptación

- `php -l` no informa errores en archivos PHP.
- `GET /api/index.php` devuelve versión Sprint 02 y disponibilidad de base de datos.
- `POST /api/auth_login.php` valida contra MySQL si hay usuario creado.
- `POST /api/telemetry.php` valida telemetría y persiste si el dispositivo existe.
- `GET /api/config.php` devuelve configuración persistida o fallback seguro.
- `GET /api/command.php` entrega comandos pendientes sin habilitar encendido remoto automático.
- `POST /api/fuel.php` valida y persiste compras si MySQL está disponible.
- `POST /api/maintenance.php` valida y persiste mantenimientos si el dispositivo existe.

## Riesgos conocidos

- Sin servidor MySQL configurado, los endpoints funcionan en modo degradado y no persisten datos.
- El login real necesita un usuario creado manualmente con hash seguro.
- El restablecimiento de contraseña todavía no envía email real; solo prepara token hasheado.
- El frontend llama por defecto a `http://localhost:8081/api`; en despliegue real debe configurarse `window.APP_API_BASE_URL`.
- Todavía no hay autorización fina por rol en cada acción; se deja para Sprint 03.

## Lo que NO se debe hacer todavía

- No exponer el backend a internet.
- No activar relés reales.
- No crear contraseñas débiles ni guardar claves en Git.
- No permitir encendido remoto sin doble validación backend/firmware.
- No considerar la persistencia como prueba de combustión real.
