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

No usar `delay()` en firmware de control. Usar temporización por `millis()`.
