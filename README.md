# Caldera Biomasa Domótica

Proyecto de domotización, automatización y monitorización remota de una caldera de biomasa Pasqualicchio / CS Marina.

El objetivo no es inventar una caldera nueva. El objetivo es respetar la lógica original, añadir registro, panel web, configuración remota y alertas, manteniendo siempre la posibilidad de volver al sistema original.

## Estado actual

**Versión:** `0.4.0-admin-segura`
**Fecha:** 2026-05-13
**Estado:** base de desarrollo con autenticación PHP, persistencia MySQL opcional y modo seguro/simulado.

Esta versión **no debe conectarse todavía a cargas reales de 230V**. La parte firmware sigue en simulación y la lógica real debe validarse en banco antes de cualquier instalación.

## Principios innegociables

1. Seguridad por encima de comodidad o automatización.
2. El sistema original debe poder mantenerse como respaldo.
3. La lógica debe respetar los manuales originales de la caldera.
4. El sinfín trabaja por tiempo, no por PID directo.
5. El tiempo de sinfín encendido debe ser igual al tiempo de pausa.
6. La web puede modificar configuración, pero el firmware debe validar límites.
7. Ninguna orden remota debe saltarse condiciones críticas de seguridad.
8. Si falla un sensor crítico, se debe pasar a modo seguro.
9. Si hay alarma, no se debe permitir encendido remoto.
10. Si no hay internet, la caldera debe continuar de forma segura con configuración local.

## Stack previsto

- Arduino Mega 2560 / Elegoo Mega 2560 R3 para control crítico local.
- ESP32 para conectividad WiFi y puente HTTP/JSON.
- PHP 8+ sin framework pesado para backend.
- MySQL/MariaDB con InnoDB y utf8mb4.
- HTML, CSS, JavaScript vanilla y Bootstrap 5 para frontend.
- Chart.js para gráficas iniciales.

## Estructura del repositorio

```text
docs/                      Documentación técnica, seguridad, Codex y lógica original.
firmware/arduino-mega/      Control crítico simulado de sensores, estados y salidas.
firmware/esp32/             Conectividad WiFi/HTTP simulada y puente serie.
server/api/                 Endpoints PHP consumidos por dispositivo y panel.
server/app/                 Núcleo PHP común, configuración, repositorios y servicios.
server/public/              Entrada pública mínima del backend.
server/sql/                 Esquema SQL inicial y seed de desarrollo de ejemplo.
server/storage/             Logs y almacenamiento futuro.
web/                        Panel Bootstrap mobile-first.
tools/                      Scripts auxiliares.
tests/                      Pruebas de humo y pruebas futuras.
```

## Backend PHP

La API usa respuestas JSON consistentes:

```json
{
  "success": true,
  "data": {},
  "error": null,
  "meta": {}
}
```

Endpoints iniciales:

- `GET /api/index.php`
- `POST /api/auth_login.php`
- `GET /api/auth_me.php`
- `POST /api/auth_logout.php`
- `POST /api/password_reset_request.php`
- `GET /api/csrf_token.php`
- `GET|POST /api/users.php`
- `GET|POST /api/devices.php`
- `POST /api/telemetry.php`
- `GET /api/config.php?device_id=caldera-01`
- `GET /api/command.php?device_id=caldera-01`
- `POST /api/command_request.php`
- `POST /api/config_ack.php`
- `POST /api/events.php`
- `GET|POST /api/fuel.php`
- `GET|POST /api/maintenance.php`

Los endpoints de dispositivo requieren cabecera:

```http
X-API-KEY: clave-local-larga-generada-fuera-de-git
```

La clave debe ser larga, local y no versionada. Los placeholders públicos como `cambiar_en_local` se rechazan para fallar cerrado ante errores de configuración.

## Configuración local

Copia el ejemplo de entorno y ajusta valores locales sin subir secretos:

```bash
cp server/.env.example server/.env
```

El archivo `server/.env` queda ignorado por Git.

## Autenticación Sprint 02

El login real usa:

- tabla `users`,
- tabla `roles`,
- `password_hash()` para crear hashes,
- `password_verify()` para validar contraseñas,
- sesiones PHP con cookie HTTP-only,
- restablecimiento preparado mediante token hasheado,
- autorización por roles para endpoints administrativos,
- protección CSRF para operaciones web mutables,
- gestión base de usuarios y dispositivos.

Para crear un hash local:

```bash
php tools/scripts/generar_hash_password.php 'una-contraseña-larga-local'
```

Después se puede usar `server/sql/seed_development.example.sql` como plantilla, sustituyendo los placeholders por hashes generados localmente.


## Administración web segura

La base administrativa ya permite separar endpoints de dispositivo y endpoints de panel:

- `csrf_token.php` entrega un token CSRF solo a sesiones autenticadas.
- `users.php` exige rol `administrador` para listar, crear o editar usuarios.
- `devices.php` exige sesión para listar y rol `administrador` con CSRF para crear o editar dispositivos.
- `command_request.php` exige sesión, rol operativo, CSRF, MySQL disponible y dispositivo registrado.

El encendido remoto (`START`) sigue bloqueado salvo que `REMOTE_START_ALLOWED=true`, y aun así el firmware debe volver a validar condiciones locales.

## Base de datos

El esquema inicial está en:

```text
server/sql/schema.sql
```

El seed local de ejemplo está en:

```text
server/sql/seed_development.example.sql
```

Incluye tablas para usuarios, roles, dispositivos, telemetría, configuración, comandos, eventos, alarmas, combustible, mantenimiento, notificaciones y ajustes del sistema.

## Frontend

El panel está en:

```text
web/index.html
```

Incluye login visual conectado al backend, dashboard con KPIs simulados, menú offcanvas móvil, sidebar escritorio, gráficas Chart.js y secciones iniciales.

Por defecto, el JavaScript llama a:

```text
http://localhost:8081/api
```

En despliegue real se debe configurar `window.APP_API_BASE_URL` antes de cargar `web/assets/js/app.js`.

## Firmware

### Arduino Mega

Archivo principal:

```text
firmware/arduino-mega/src/main.ino
```

Incluye `SIMULATION_MODE = true`, máquina de estados base, sensores simulados, salidas simuladas, seguridad local básica, ciclo del sinfín ON = OFF y telemetría JSON por serie.

### ESP32

Archivo principal:

```text
firmware/esp32/src/main.ino
```

Incluye `SIMULATION_MODE = true`, placeholders WiFi, envío HTTP simulado, consulta simulada de configuración y comandos, y puente serie preparado.

## Cómo probar en desarrollo

### Validar sintaxis PHP

```bash
find server -name '*.php' -print0 | xargs -0 -n1 php -l
```

### Servir backend

```bash
php -S localhost:8081 -t server
```

### Ejecutar prueba de humo

```bash
tests/backend/smoke_api.sh
```

### Servir panel web

```bash
php -S localhost:8080 -t web
```

## Advertencia de seguridad

Este repositorio está preparado para desarrollo y banco de pruebas. No está validado para controlar una caldera real ni cargas de 230V. Cualquier integración física debe pasar por revisión eléctrica, pruebas con cargas seguras y validación de la lógica original.
