# Versión del proyecto

## Versión actual

**0.2.0-sprint-01-base**

## Fecha

2026-05-12

## Estado

Base funcional inicial preparada para desarrollo real en modo seguro/simulado.

## Incluye

- Documentación Sprint 01.
- README principal actualizado.
- Contrato API ampliado.
- Esquema SQL inicial MySQL con tablas principales.
- Núcleo PHP mínimo sin framework pesado.
- Endpoints API base para telemetría, configuración, comandos, ACK, eventos, combustible y mantenimiento.
- `.env.example` sin credenciales reales.
- Panel Bootstrap 5 mobile-first con datos simulados.
- Menú offcanvas móvil y sidebar escritorio.
- KPIs y gráficas Chart.js simuladas.
- Firmware Arduino Mega en modo simulación segura.
- Firmware ESP32 en modo simulación segura.

## Pendiente

- Integrar persistencia real PDO en endpoints.
- Implementar autenticación completa con sesiones y recuperación de contraseña.
- Añadir pruebas automatizadas.
- Validar sensores reales en banco.
- Revisar la lógica original contra manuales antes de activar hardware.
- Definir inventario final de sensores.
