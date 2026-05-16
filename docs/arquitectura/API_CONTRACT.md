# Contrato API

## Formato común de respuesta

Todas las respuestas JSON siguen esta estructura:

```json
{
  "success": true,
  "data": {},
  "error": null,
  "meta": {}
}
```

En caso de error:

```json
{
  "success": false,
  "data": {},
  "error": {
    "code": "codigo_error",
    "message": "Descripción en español.",
    "details": []
  },
  "meta": {}
}
```

## Modo de persistencia

Los endpoints intentan usar MySQL mediante PDO. Si MySQL no está disponible, algunos endpoints responden en modo degradado seguro con `meta.persistence = "skipped"` o `meta.source = "fallback_safe"`.

Este modo degradado sirve para desarrollo, no para producción.

## Autenticación de usuarios

### Login

```http
POST /api/auth_login.php
```

Payload:

```json
{
  "usuario": "admin",
  "contrasena": "contraseña-local"
}
```

Requiere que exista un usuario activo en `users` con `password_hash` válido.

### Usuario actual

```http
GET /api/auth_me.php
```

Devuelve la sesión PHP autenticada.

### Logout

```http
POST /api/auth_logout.php
```

Cierra la sesión PHP actual.

### Solicitud de restablecimiento

```http
POST /api/password_reset_request.php
```

Payload:

```json
{
  "email": "admin@example.com"
}
```

La respuesta nunca revela si el email existe. En Sprint 02 no se envían correos reales.

## Autenticación de dispositivo

El dispositivo debe enviar una clave API por cabecera:

```http
X-API-KEY: clave-local-larga-generada-fuera-de-git
```

La API key se valida contra `DEVICE_API_KEY` solo si está configurada, no es un placeholder público y tiene longitud suficiente; si no, se intenta validar contra `devices.api_key_hash` cuando existe `device_id` y MySQL está disponible.


## Endpoints web autenticados

### Token CSRF

```http
GET /api/csrf_token.php
```

Requiere sesión PHP autenticada. Devuelve `csrf_token` para operaciones web mutables.

### Usuarios

```http
GET /api/users.php
POST /api/users.php
```

Requiere rol `administrador`. `POST` requiere cabecera `X-CSRF-TOKEN` y permite crear o actualizar usuarios sin exponer hashes.

### Dispositivos

```http
GET /api/devices.php
POST /api/devices.php
```

`GET` requiere sesión. `POST` requiere rol `administrador` y `X-CSRF-TOKEN`. La API key inicial se guarda con `password_hash()` y no se devuelve.

### Solicitud web de comandos

```http
POST /api/command_request.php
```

Requiere rol `administrador`, `operador` o `mantenimiento`, además de `X-CSRF-TOKEN`. Los comandos se guardan como pendientes y el firmware debe validarlos antes de actuar.


## Rate limiting

Los endpoints sensibles pueden devolver:

```http
429 Too Many Requests
```

Formato de error:

```json
{
  "success": false,
  "data": {},
  "error": {
    "code": "rate_limit_excedido",
    "message": "Demasiadas peticiones. Inténtalo de nuevo más tarde.",
    "details": {
      "retry_after_seconds": 60
    }
  },
  "meta": {}
}
```

Límites iniciales:

- Login: 5 intentos por usuario/IP cada 5 minutos.
- Restablecimiento: 3 solicitudes por email/IP cada 15 minutos.
- Dispositivo: 120 peticiones por API key/IP cada minuto.

## Estado de API

```http
GET /api/index.php
```

Devuelve versión, estado, disponibilidad de base de datos y endpoints disponibles.

## Dashboard

```http
GET /api/dashboard.php?device_id=caldera-01
```

Devuelve un snapshot agregado para el panel web con KPIs y secciones. Requiere sesión web y uno de los roles `administrador`, `operador`, `solo_lectura` o `mantenimiento`.

Respuesta resumida:

```json
{
  "success": true,
  "data": {
    "device_id": "caldera-01",
    "source": "fallback_safe",
    "generated_at": "2026-05-15T00:00:00+00:00",
    "kpis": [],
    "sections": {}
  },
  "error": null,
  "meta": {
    "persistence": "fallback_safe",
    "authenticated_required": true
  }
}
```

## Telemetría

```http
POST /api/telemetry.php
```

Requiere `X-API-KEY`.

Payload previsto:

```json
{
  "device_id": "caldera-01",
  "firmware": "0.3.1-sprint-02-hardening",
  "state": "NORMAL",
  "water_temp": 72.5,
  "smoke_temp": 210.3,
  "fuel_level": 78,
  "outputs": {
    "auger": false,
    "igniter": false,
    "fan_primary_pct": 60,
    "fan_secondary_pct": 40,
    "pump": true
  }
}
```

Si MySQL está disponible y el dispositivo está registrado, se inserta en `telemetry`.

## Configuración

```http
GET /api/config.php?device_id=caldera-01
```

Requiere `X-API-KEY`.

Devuelve configuración persistida en `boiler_config` o fallback seguro.

Regla crítica:

- `auger_cycle_seconds` representa tanto ON como OFF.
- El firmware debe validar de nuevo cualquier parámetro recibido.

## Comandos

```http
GET /api/command.php?device_id=caldera-01
```

Requiere `X-API-KEY`.

Comandos previstos:

- `START`
- `STOP`
- `RESET_ALARM`
- `ENTER_MAINTENANCE`
- `EXIT_MAINTENANCE`

Los comandos se leen de MySQL si existe dispositivo registrado, pero `remote_start_enabled` sigue siendo `false` como recordatorio de seguridad.

## ACK de configuración

```http
POST /api/config_ack.php
```

Requiere `X-API-KEY`.

Payload previsto:

```json
{
  "device_id": "caldera-01",
  "config_version": 1,
  "status": "aplicada",
  "message": "Configuración validada por firmware."
}
```

`status` puede ser `aplicada` o `rechazada`.

## Eventos

```http
POST /api/events.php
```

Requiere `X-API-KEY` si el origen es dispositivo.

Severidades:

- `info`
- `aviso`
- `error`
- `critico`

Tipos:

- `estado`
- `configuracion`
- `orden`
- `comunicacion`
- `sensor`
- `mantenimiento`
- `sistema`

## Combustible

```http
GET /api/fuel.php
POST /api/fuel.php
```

Tipos de combustible:

- `pellet`
- `hueso_aceituna`
- `otro`

`POST` registra compras si MySQL está disponible.

## Mantenimiento

```http
GET /api/maintenance.php
POST /api/maintenance.php
```

Tipos de mantenimiento:

- `limpieza`
- `revision`
- `pieza`
- `reparacion`

`POST` requiere `device_id` registrado si MySQL está disponible.

## Reglas de seguridad

- El backend valida rangos y catálogos.
- El firmware vuelve a validar rangos.
- El firmware puede rechazar cualquier configuración peligrosa.
- Los comandos remotos nunca deben saltarse sensores críticos.
- Si hay alarma activa, no debe permitirse encendido remoto.
- Si falla un sensor crítico, debe prevalecer el modo seguro.
