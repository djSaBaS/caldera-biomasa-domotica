# CHANGELOG

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
