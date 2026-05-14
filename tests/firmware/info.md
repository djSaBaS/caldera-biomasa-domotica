# Información de carpeta: tests/firmware

## Propósito

Pruebas futuras de firmware.

## Normas

- Mantener nombres claros.
- Documentar cambios relevantes.
- No guardar credenciales reales.
- Mantener coherencia con la documentación de `docs/`.

## Prueba disponible

- `validate_firmware_contract.php`: validación estática del contrato mínimo Arduino/ESP32, cache offline y ausencia de `delay`.

- `compile_firmware.sh`: compilación reproducible con Arduino CLI para Arduino Mega 2560 y ESP32 en CI.
- `validate_connection_diagrams.php`: validación estática de diagramas SVG y advertencias de pinout pendiente.
