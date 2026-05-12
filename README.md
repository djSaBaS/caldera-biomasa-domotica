# Caldera Biomasa Domótica

Proyecto de domotización, automatización y monitorización remota de una caldera de biomasa Pasqualicchio / CS Marina.

El objetivo no es inventar una caldera nueva, sino replicar de forma fiel el funcionamiento original de la centralita, añadir conectividad, registro histórico, panel web, configuración remota y alertas, manteniendo siempre la posibilidad de volver al sistema original.

## Stack previsto

- Firmware principal: Arduino Mega 2560 / Elegoo Mega 2560 R3.
- Conectividad: ESP32.
- Backend: PHP + MySQL.
- Frontend: HTML, CSS y JavaScript.
- Comunicación inicial recomendada: HTTP/JSON con modelo pull desde el dispositivo.
- Alertas futuras: email, Telegram y WhatsApp.

## Principios innegociables

1. Seguridad por encima de comodidad o automatización.
2. El sistema original debe poder mantenerse como respaldo.
3. La lógica debe respetar los manuales originales de la caldera.
4. El sinfín trabaja por tiempo, no por PID directo.
5. El tiempo de sinfín encendido debe ser igual al tiempo de pausa.
6. La web puede modificar configuración, pero el firmware debe validar los límites.
7. Ninguna orden remota debe saltarse condiciones críticas de seguridad.

## Estructura del repositorio

```text
docs/                      Documentación técnica, seguridad, Codex y lógica original.
firmware/arduino-mega/      Control crítico de sensores, relés, estados y seguridad.
firmware/esp32/             Conectividad WiFi, API, telemetría y comandos.
server/                     Backend PHP + MySQL.
web/                        Panel visual y assets frontend.
tools/                      Scripts auxiliares.
tests/                      Pruebas futuras.
```

## Documentos importantes

- `docs/codex/PROMPT_CODEX_MASTER.md`
- `docs/arquitectura/LOGICA_ORIGINAL_CALDERA.md`
- `docs/arquitectura/ARQUITECTURA_GENERAL.md`
- `docs/seguridad/REGLAS_SEGURIDAD.md`
- `docs/arquitectura/API_CONTRACT.md`
- `docs/arquitectura/HARDWARE.md`
- `version.md`

## Estado actual

Carga inicial del repositorio con estructura, documentación base y archivos mínimos para empezar a trabajar con Codex desde GitHub.
