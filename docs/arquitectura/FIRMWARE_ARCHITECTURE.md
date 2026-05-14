# Arquitectura firmware

## Arduino Mega

Controlador crítico local.

Responsabilidades:

- Leer sensores.
- Ejecutar máquina de estados.
- Controlar relés.
- Controlar dimmers.
- Mostrar información en LCD1602.
- Validar seguridad.
- Recibir configuración desde ESP32.
- Enviar estado al ESP32.

## ESP32

Puente de conectividad.

Responsabilidades:

- Gestionar WiFi.
- Enviar telemetría al backend.
- Consultar configuración y comandos.
- Enviar configuración validada al Arduino.
- No tomar decisiones críticas de combustión.

## Comunicación Arduino ↔ ESP32

Inicialmente se recomienda serial/UART con mensajes JSON compactos o protocolo de texto delimitado.

## Regla técnica

No usar `delay` en firmware de control. Usar temporización por `millis()`.

## Configuración offline Sprint 03

La caldera no debe depender de internet para mantener una operación segura. La arquitectura queda separada así:

- Arduino Mega mantiene la lógica crítica, los límites térmicos y la última configuración válida en EEPROM.
- ESP32 actúa como puente HTTP y nunca decide por sí solo condiciones críticas de combustión.
- Backend/MySQL es la fuente remota de configuración, pero no es requisito para que Arduino conserve los últimos parámetros validados.

## Protocolo serie interno

El enlace recomendado entre placas es UART dedicado:

- Arduino Mega `Serial1` hacia ESP32 `Serial2`.
- Velocidad inicial `115200` baudios.
- Tramas terminadas en salto de línea.

Tipos de trama:

```text
TEL:{json}
CFG:version=2;auger=12;fan1=58;fan2=42;pump=58;target=74;maintenance=81;safety=90;telemetry=15;poll=45;notifications=1
ACK:{json}
```

## Flujo de datos hacia MySQL

1. Arduino genera `TEL:{json}` con estado, sensores, salidas y versión de configuración activa.
2. ESP32 retira el prefijo `TEL:` y envía el JSON a `POST /api/telemetry.php`.
3. El backend valida API key, `device_id` y estado de caldera.
4. Si MySQL está disponible y el dispositivo existe, `TelemetryRepository` persiste la muestra.

## Flujo de configuración remota con cache local

1. ESP32 consulta `GET /api/config.php?device_id=caldera-01`.
2. ESP32 extrae parámetros de firmware de la respuesta JSON del backend.
3. ESP32 envía una trama `CFG:` compacta al Arduino Mega.
4. Arduino valida rangos localmente antes de aplicar.
5. Arduino guarda la configuración válida en EEPROM.
6. Arduino responde `ACK:{json}`.
7. ESP32 envía ese ACK a `POST /api/config_ack.php` cuando hay red.

## Advertencia realista

El parsing JSON del ESP32 está implementado sin ArduinoJson para no añadir dependencias todavía. Esto sirve para banco de pruebas y contrato inicial, pero para producción conviene evaluar ArduinoJson con límites de memoria definidos, pruebas de respuestas corruptas y watchdog de comunicaciones.
