# CHANGELOG

## [0.3.1-sprint-02-hardening] - 2026-05-13

### Cambiado

- Sustituida la lectura `readStringUntil()` del ESP32 por acumulación no bloqueante en búfer estático.
- Cacheado el JSON de `php://input` para evitar consumir el stream más de una vez por petición.
- Encapsulada la creación de PDO para devolver errores genéricos sin exponer credenciales ni DSN.
- Actualizada la API key de ejemplo del ESP32 a un placeholder documental rechazado por backend.

### Seguridad

- La validación de API key de dispositivo falla cerrada si `DEVICE_API_KEY` falta, está vacía, es corta o usa un placeholder público.
- `.env.example` documenta que el valor de ejemplo debe reemplazarse por una clave larga local.
- La prueba de humo verifica que `cambiar_en_local` devuelve HTTP 401.

## [0.3.0-sprint-02-persistencia-auth] - 2026-05-13

### Añadido

- Documento `docs/codex/SPRINT_02.md` con alcance y criterios del sprint.
- Endpoints de autenticación `auth_login.php`, `auth_me.php`, `auth_logout.php` y `password_reset_request.php`.
- Servicio `AuthService` con sesiones PHP, `password_verify` y token de restablecimiento hasheado.
- Helper `Validation` para centralizar validaciones de entrada.
- Repositorios PHP para dispositivos, telemetría, configuración, comandos, eventos, combustible y mantenimiento.
- Persistencia MySQL opcional en endpoints clave cuando la base está disponible.
- Validación de API key contra variable de entorno o hash en tabla `devices`.
- Carga local de `server/.env` y `.gitignore` para secretos locales.
- Script `tools/scripts/generar_hash_password.php` para crear hashes de usuarios locales.
- Script `tests/backend/smoke_api.sh` para pruebas de humo del backend.
- Archivo `server/sql/seed_development.example.sql` para datos locales sin secretos reales.

### Cambiado

- Actualizado el panel para llamar al login y restablecimiento de contraseña reales del backend.
- Actualizado `README.md`, `version.md` y contrato API con instrucciones Sprint 02.
- Actualizado `/api/index.php` para informar disponibilidad de MySQL y endpoints nuevos.

### Seguridad

- El encendido remoto sigue sin habilitarse automáticamente.
- La API puede funcionar en modo degradado sin MySQL, pero informa que no persiste.
- El restablecimiento de contraseña no revela si el email existe.
- Las salidas físicas siguen en modo seguro/simulado.

## [0.2.0-sprint-01-base] - 2026-05-12

### Añadido

- Documento `docs/codex/SPRINT_01.md` con objetivo, tareas, criterios, pruebas y riesgos.
- Esquema SQL inicial en `server/sql/schema.sql`.
- Núcleo PHP común con `Database`, `JsonResponse`, `Request` y `ApiKeyValidator`.
- Catálogo de configuración de caldera con límites y explicaciones.
- Endpoints API iniciales para comandos, ACK de configuración, eventos, combustible y mantenimiento.
- `.env.example` sin credenciales reales.
- Panel Bootstrap mobile-first con offcanvas, sidebar, KPIs y gráficas simuladas.
- Firmware Arduino Mega con máquina de estados, seguridad básica y telemetría simulada.
- Firmware ESP32 con placeholders WiFi, HTTP simulado y puente serie preparado.

### Cambiado

- Actualizado `README.md` con instrucciones de prueba y advertencias de seguridad.
- Actualizado `version.md` a `0.2.0-sprint-01-base`.
- Actualizado contrato API para reflejar endpoints y formato JSON común.

### Seguridad

- Se mantiene `SIMULATION_MODE = true` en firmware.
- No se incluyen credenciales reales.
- Los comandos remotos devuelven cola vacía en esta fase.
- El encendido remoto queda explícitamente deshabilitado en modo base.

## [0.1.0-inicial] - 2026-05-12

### Añadido

- Estructura inicial del proyecto.
- Documentación para Codex.
- Documentación de arquitectura.
- Documentación de seguridad.
- Archivos informativos por carpeta.
- Placeholders iniciales de firmware, backend y web.
