#!/usr/bin/env bash
# Comentario: Activar modo estricto para detener la compilación ante cualquier error.
set -euo pipefail

# Comentario: Resolver la raíz del repositorio desde la ubicación de este script.
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# Comentario: Entrar en la raíz para usar rutas reproducibles en local y CI.
cd "${REPO_ROOT}"

# Comentario: Definir URL oficial del índice de placas ESP32 para Arduino CLI.
ESP32_ADDITIONAL_URL="https://espressif.github.io/arduino-esp32/package_esp32_index.json"

# Comentario: Verificar que Arduino CLI está instalado antes de compilar firmware.
arduino-cli version

# Comentario: Fijar plataformas y parser; permitir reutilizar instalaciones verificadas en banco sin red.
if [[ "${ARDUINO_SKIP_INSTALL:-0}" != "1" ]]; then
  arduino-cli core update-index
  arduino-cli core install arduino:avr@1.8.8
  arduino-cli --additional-urls "${ESP32_ADDITIONAL_URL}" core update-index
  arduino-cli --additional-urls "${ESP32_ADDITIONAL_URL}" core install esp32:esp32@3.3.12
  arduino-cli lib install ArduinoJson@7.4.2
fi

# Comentario: Crear directorio temporal para adaptar la estructura actual al formato clásico de sketch Arduino.
TEMP_SKETCH_ROOT="$(mktemp -d)"

# Comentario: Asegurar limpieza del directorio temporal al terminar el script.
trap 'rm -rf "${TEMP_SKETCH_ROOT}"' EXIT

# Comentario: Crear carpeta temporal de sketch para Arduino Mega.
mkdir -p "${TEMP_SKETCH_ROOT}/caldera_arduino_mega"

# Comentario: Copiar firmware Mega usando nombre de archivo igual al sketch temporal.
cp firmware/arduino-mega/src/main.ino "${TEMP_SKETCH_ROOT}/caldera_arduino_mega/caldera_arduino_mega.ino"

# Comentario: Crear carpeta temporal de sketch para ESP32.
mkdir -p "${TEMP_SKETCH_ROOT}/caldera_esp32"

# Comentario: Copiar firmware ESP32 usando nombre de archivo igual al sketch temporal.
cp firmware/esp32/src/main.ino "${TEMP_SKETCH_ROOT}/caldera_esp32/caldera_esp32.ino"

# Comentario: Copiar el modelo común para que los sketches temporales usen exactamente el código probado.
cp firmware/common/*.h "${TEMP_SKETCH_ROOT}/caldera_arduino_mega/"
# Comentario: Compartir el contrato y el parser JSON con el sketch ESP32.
cp firmware/common/*.h "${TEMP_SKETCH_ROOT}/caldera_esp32/"

# Comentario: Compilar Arduino Mega 2560 con CPU ATmega2560 explícita.
arduino-cli compile --fqbn arduino:avr:mega:cpu=atmega2560 "${TEMP_SKETCH_ROOT}/caldera_arduino_mega"

# Comentario: Compilar ESP32 genérico inicial usado para validar el puente de conectividad.
arduino-cli compile --fqbn esp32:esp32:esp32 "${TEMP_SKETCH_ROOT}/caldera_esp32"
