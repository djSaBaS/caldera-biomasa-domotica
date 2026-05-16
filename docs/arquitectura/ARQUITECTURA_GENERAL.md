# Arquitectura general

## Resumen

El proyecto se organiza como un monorepo con tres áreas principales:

1. Firmware Arduino Mega para control crítico local.
2. Firmware ESP32 para conectividad y puente con backend.
3. Backend PHP, MySQL y panel web Bootstrap.

## Flujo de datos

```text
Sensores / actuadores
        ↓
Arduino Mega
        ↓ serial/UART
ESP32
        ↓ HTTP JSON
Backend PHP
        ↓
MySQL
        ↓
Panel Web
```

## Flujo inverso de configuración

```text
Panel Web
   ↓
Backend PHP / MySQL
   ↓ consulta periódica
ESP32
   ↓ serial/UART
Arduino Mega
   ↓ validación local
Parámetros activos
```

## Separación de responsabilidades

### Arduino Mega

- Lee sensores críticos.
- Ejecuta máquina de estados.
- Aplica seguridad local.
- Controla salidas solo cuando se retire explícitamente la simulación.
- Debe seguir funcionando de forma segura sin internet.

### ESP32

- Gestiona WiFi.
- Envía telemetría.
- Descarga configuración y comandos.
- Actúa como puente, no como controlador crítico principal.

### Backend PHP

- Recibe telemetría.
- Sirve configuración validada.
- Prepara comandos remotos con trazabilidad.
- Registra eventos, alarmas, combustible y mantenimiento.
- Usa PDO y MySQL sin framework pesado en esta fase.

### Web

- Muestra estado y KPIs.
- Permite preparar cambios de parámetros.
- Muestra históricos y registros.
- Gestionará usuarios y roles en fases siguientes.
- Usa Bootstrap 5 mobile-first.

## Decisiones de Sprint 01

- Mantener firmware en `SIMULATION_MODE = true`.
- No activar relés reales.
- Devolver cola de comandos vacía.
- Crear SQL inicial completo antes de persistencia real en endpoints.
- Mantener frontend con datos simulados hasta conectar API autenticada.
