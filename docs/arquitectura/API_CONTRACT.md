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

## Autenticación de dispositivo

El dispositivo debe enviar una clave API por cabecera:

```http
X-API-KEY: cambiar_en_local
```

La clave del ejemplo no es real y debe configurarse fuera de Git.

## Estado de API

```http
GET /api/index.php
```

Devuelve versión, estado y endpoints disponibles.

## Telemetría

```http
POST /api/telemetry.php
```

Requiere `X-API-KEY`.

Payload previsto:

```json
{
  "device_id": "caldera-01",
  "firmware": "0.2.0-sprint-01-base",
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
  },
  "alarms": []
}
```

## Configuración

```http
GET /api/config.php?device_id=caldera-01
```

Requiere `X-API-KEY`.

Devuelve configuración activa y catálogo de parámetros con unidad, mínimo, máximo y explicación.

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

En Sprint 01 la cola se devuelve vacía para evitar activaciones remotas reales.

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

`status` puede ser:

- `aplicada`
- `rechazada`

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

## Combustible

```http
GET /api/fuel.php
POST /api/fuel.php
```

Tipos de combustible:

- `pellet`
- `hueso_aceituna`
- `otro`

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

## Reglas de seguridad

- El backend valida rangos.
- El firmware vuelve a validar rangos.
- El firmware puede rechazar cualquier configuración peligrosa.
- Los comandos remotos nunca deben saltarse sensores críticos.
- Si hay alarma activa, no debe permitirse encendido remoto.
- Si falla un sensor crítico, debe prevalecer el modo seguro.
