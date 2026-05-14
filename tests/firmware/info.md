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
