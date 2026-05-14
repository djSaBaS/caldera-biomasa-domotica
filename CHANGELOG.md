# CHANGELOG

## [0.4.4-ci-arduino-cli-diagramas] - 2026-05-14

### Añadido

- Instalación de Arduino CLI en GitHub Actions mediante `arduino/setup-arduino-cli@v2`.
- Script `tests/firmware/compile_firmware.sh` para compilar Arduino Mega 2560 y ESP32 desde CI.
- Validación estática de diagramas de conexión en `tests/firmware/validate_connection_diagrams.php`.
- Diagramas SVG para UART Arduino Mega↔ESP32, flujo firmware-backend y accesorios con pinout pendiente.
- Documento `docs/arquitectura/CONEXIONES.md` con conexiones reales documentadas y advertencias de seguridad.

### Seguridad

- Los diagramas no asignan pines a cargas de caldera sin respaldo del firmware o esquema eléctrico validado.
- Los accesorios de 230V quedan explícitamente marcados como no cableables hasta definir pinout, aislamiento y pruebas de banco.

## [0.4.3-firmware-offline-config] - 2026-05-14

### Añadido

- Firmware Arduino Mega con cache EEPROM de la última configuración válida para operación autónoma sin internet.
- Protocolo serie `TEL`/`CFG`/`ACK` entre Arduino Mega y ESP32 para telemetría, configuración remota y confirmaciones.
- Firmware ESP32 con puente HTTP para enviar telemetría, consultar configuración y reenviar ACK al backend.
- Validación estática `tests/firmware/validate_firmware_contract.php` integrada en la puerta de calidad.

### Seguridad

- Arduino valida rangos localmente antes de aplicar y persistir parámetros recibidos desde MySQL.
- La configuración remota no sustituye a las reglas críticas locales ni elimina el modo simulación por defecto.

## [0.4.2-demo-ci-tests] - 2026-05-13

### Añadido

- Seed SQL `server/sql/seed_demo_preview.sql` con datos ficticios para previsualización del panel en modo demo.
- Pruebas unitarias iniciales para catálogo de configuración y política de API key.
- Validador estático del seed demo y runner local `tests/backend/run_quality_checks.sh`.
- Workflow `.github/workflows/ci.yml` con lint PHP, JS, Bash, pruebas unitarias, validación de seed y smoke test HTTP.

### Seguridad

- El seed demo usa hashes ficticios no productivos y documenta que no debe usarse como credencial real.
- CI rechaza placeholders o cambios básicos que rompan lint, tests o contrato mínimo demo.

## [0.4.1-rate-limit-basico] - 2026-05-13

### Añadido

- `RateLimiter` local sin dependencias para limitar peticiones sensibles por ámbito e IP.
- Rate limit en `auth_login.php`, `password_reset_request.php` y validación de API key de dispositivo.
- Carpeta documentada `server/storage/rate-limit/` para contadores temporales ignorados por Git.

### Seguridad

- Login limitado a 5 intentos por usuario/IP cada 5 minutos.
- Restablecimiento de contraseña limitado a 3 solicitudes por email/IP cada 15 minutos.
- Endpoints de dispositivo limitados a 120 peticiones por API key/IP cada minuto.

## [0.4.0-admin-segura] - 2026-05-13

### Añadido

- Protección CSRF centralizada para operaciones web autenticadas.
- Servicio de autorización por roles para endpoints administrativos.
- Repositorio y endpoint `users.php` para listar, crear y editar usuarios sin exponer hashes.
- Endpoint `devices.php` para inventario de dispositivos con API key hasheada.
- Endpoint `csrf_token.php` para entregar token CSRF a sesiones autenticadas.
- Endpoint `command_request.php` para crear comandos auditables desde el panel.

### Cambiado

- `auth_login.php` devuelve token CSRF junto a la sesión autenticada.
- `auth_logout.php` exige sesión y token CSRF para evitar cierres inducidos.
- `CommandRepository` permite crear comandos pendientes y registrar historial inicial.
- El frontend conserva el token CSRF recibido tras login para operaciones posteriores.

### Seguridad

- El comando `START` queda bloqueado salvo `REMOTE_START_ALLOWED=true`.
- Los comandos web requieren sesión, rol autorizado, CSRF, MySQL disponible y dispositivo registrado.
- La API key de dispositivo nunca se devuelve al crear dispositivos.

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
