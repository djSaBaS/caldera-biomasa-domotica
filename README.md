# Caldera Biomasa Domótica

Proyecto de domotización, automatización y monitorización remota de una caldera de biomasa Pasqualicchio / CS Marina.

El objetivo no es inventar una caldera nueva. El objetivo es respetar la lógica original, añadir registro, panel web, configuración remota y alertas, manteniendo siempre la posibilidad de volver al sistema original.

## Estado actual

**Versión:** `0.2.0-sprint-01-base`  
**Fecha:** 2026-05-12  
**Estado:** base funcional inicial en modo seguro/simulado.

Esta versión prepara el desarrollo real, pero **no debe conectarse todavía a cargas reales de 230V**.

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
server/app/                 Núcleo PHP común, configuración y servicios.
server/public/              Entrada pública mínima del backend.
server/sql/                 Esquema SQL inicial.
server/storage/             Logs y almacenamiento futuro.
web/                        Panel Bootstrap mobile-first.
tools/                      Scripts auxiliares futuros.
tests/                      Pruebas futuras y criterios mínimos.
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
- `POST /api/telemetry.php`
- `GET /api/config.php?device_id=caldera-01`
- `GET /api/command.php?device_id=caldera-01`
- `POST /api/config_ack.php`
- `POST /api/events.php`
- `GET|POST /api/fuel.php`
- `GET|POST /api/maintenance.php`

Los endpoints de dispositivo requieren cabecera:

```http
X-API-KEY: cambiar_en_local
```

La clave anterior es un placeholder. En desarrollo real debe configurarse fuera de Git.

## Base de datos

El esquema inicial está en:

```text
server/sql/schema.sql
```

Incluye tablas para:

- usuarios y roles,
- dispositivos,
- telemetría,
- configuración e histórico,
- comandos e histórico,
- eventos y alarmas,
- compras y consumo de combustible,
- mantenimiento,
- notificaciones,
- ajustes del sistema.

## Frontend

El panel está en:

```text
web/index.html
```

Incluye:

- login visual preparado para autenticación real,
- dashboard con KPIs simulados,
- menú offcanvas en móvil,
- sidebar en escritorio,
- gráficas Chart.js,
- secciones iniciales de estado, usuarios, programación, configuración, logs, combustible, mantenimiento, notificaciones y ajustes.

## Firmware

### Arduino Mega

Archivo principal:

```text
firmware/arduino-mega/src/main.ino
```

Incluye:

- `SIMULATION_MODE = true`,
- máquina de estados original base,
- sensores simulados,
- salidas simuladas,
- seguridad local básica,
- ciclo del sinfín con ON = OFF,
- telemetría JSON por serie.

### ESP32

Archivo principal:

```text
firmware/esp32/src/main.ino
```

Incluye:

- `SIMULATION_MODE = true`,
- placeholders WiFi,
- envío HTTP simulado,
- consulta simulada de configuración y comandos,
- puente serie preparado.

## Cómo probar en desarrollo

### Servir panel web

```bash
php -S localhost:8080 -t web
```

### Servir backend

```bash
php -S localhost:8081 -t server
```

### Probar API base

```bash
curl http://localhost:8081/api/index.php
```

### Probar telemetría simulada

```bash
curl -X POST http://localhost:8081/api/telemetry.php \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: cambiar_en_local" \
  -d '{"device_id":"caldera-01","state":"NORMAL"}'
```

## Documentos importantes

- `docs/codex/PROMPT_CODEX_MASTER.md`
- `docs/codex/SPRINT_01.md`
- `docs/arquitectura/LOGICA_ORIGINAL_CALDERA.md`
- `docs/arquitectura/ARQUITECTURA_GENERAL.md`
- `docs/seguridad/REGLAS_SEGURIDAD.md`
- `docs/arquitectura/API_CONTRACT.md`
- `docs/arquitectura/HARDWARE.md`
- `docs/DECISIONS.md`
- `version.md`

## Advertencia de seguridad

Este repositorio está preparado para desarrollo y banco de pruebas. No está validado para controlar una caldera real ni cargas de 230V. Cualquier integración física debe pasar por revisión eléctrica, pruebas con cargas seguras y validación de la lógica original.
