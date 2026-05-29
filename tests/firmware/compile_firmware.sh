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

# Comentario: Actualizar índice estándar de plataformas Arduino.
arduino-cli core update-index

# Comentario: Instalar core AVR oficial necesario para Arduino Mega 2560.
arduino-cli core install arduino:avr

# Comentario: Actualizar índice incluyendo el proveedor ESP32 usado por Arduino CLI.
arduino-cli --additional-urls "${ESP32_ADDITIONAL_URL}" core update-index

# Comentario: Instalar core ESP32 necesario para compilar el puente WiFi.
arduino-cli --additional-urls "${ESP32_ADDITIONAL_URL}" core install esp32:esp32

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

# Comentario: Compilar Arduino Mega 2560 con CPU ATmega2560 explícita.
arduino-cli compile --fqbn arduino:avr:mega:cpu=atmega2560 "${TEMP_SKETCH_ROOT}/caldera_arduino_mega"

# Comentario: Compilar ESP32 genérico inicial usado para validar el puente de conectividad.
arduino-cli compile --fqbn esp32:esp32:esp32 "${TEMP_SKETCH_ROOT}/caldera_esp32"
