# Contrato API

## Autenticación de dispositivo

El dispositivo debe enviar una clave API en cabecera.

```http
X-API-KEY: valor_seguro
```

## Telemetría

Endpoint previsto:

```http
POST /api/telemetry.php
```

Payload previsto:

```json
{
  "device_id": "caldera-01",
  "firmware": "0.1.0",
  "state": "NORMAL",
  "temp_water": 72.5,
  "temp_smoke": 210.3,
  "pellet_level": 78,
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

Endpoint previsto:

```http
GET /api/config.php?device_id=caldera-01
```

Respuesta prevista:

```json
{
  "config_version": 1,
  "mode": "auto",
  "auger_cycle_seconds": 10,
  "fan_primary_pct": 60,
  "fan_secondary_pct": 40,
  "pump_on_temp": 60,
  "target_temp": 75,
  "maintenance_temp": 80,
  "safety_temp": 90,
  "telemetry_interval_seconds": 10,
  "config_poll_interval_seconds": 30
}
```

## Comandos

Endpoint previsto:

```http
GET /api/command.php?device_id=caldera-01
```

Comandos previstos:

- START
- STOP
- RESET_ALARM
- ENTER_MAINTENANCE
- EXIT_MAINTENANCE

## ACK de configuración

Endpoint futuro:

```http
POST /api/config_ack.php
```

## Reglas

- El backend valida rangos.
- El firmware vuelve a validar rangos.
- El firmware puede rechazar una configuración peligrosa.
- Los comandos remotos nunca deben saltarse sensores críticos.
