# Arquitectura general

## Resumen

El proyecto se organiza como un monorepo con tres áreas principales:

1. Firmware Arduino Mega: control crítico local.
2. Firmware ESP32: conectividad y puente con backend.
3. Backend/Web: API, base de datos, panel y alertas.

## Flujo de datos

```text
Sensores / actuadores
        ↓
Arduino Mega
        ↓ serial/UART
ESP32
        ↓ HTTPS/HTTP JSON
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

## Decisión inicial

Se usa un único repositorio porque firmware, backend y web están muy acoplados. Cambiar un parámetro de firmware puede requerir cambiar API, SQL y panel.

## Separación de responsabilidades

### Arduino Mega

- Lee sensores críticos.
- Ejecuta máquina de estados.
- Controla relés, dimmers y LCD.
- Aplica seguridad local.
- Debe seguir funcionando de forma segura aunque no haya internet.

### ESP32

- Gestiona WiFi.
- Envía telemetría.
- Descarga configuración y comandos.
- Actúa como puente, no como controlador crítico principal.

### Backend PHP

- Recibe telemetría.
- Guarda histórico.
- Sirve configuración.
- Valida órdenes.
- Registra eventos.
- Lanza alertas.

### Web

- Muestra estado.
- Permite cambiar parámetros.
- Muestra histórico.
- Muestra alarmas.
- Gestiona usuarios y roles.
