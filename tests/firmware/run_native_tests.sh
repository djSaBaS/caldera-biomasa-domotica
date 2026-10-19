#!/usr/bin/env bash
# Comentario: Detener la verificación ante errores del compilador o del modelo.
set -euo pipefail
# Comentario: Ejecutar desde la raíz del repositorio para compartir rutas con CI.
cd "$(dirname "$0")/../.."
# Comentario: Permitir una instalación local fija sin descargar dependencias durante las pruebas.
CALDERA_JSON_INCLUDE="${ARDUINOJSON_INCLUDE:-${ARDUINO_DIRECTORIES_USER:-$HOME/Arduino}/libraries/ArduinoJson/src}"
# Comentario: Preparar artefactos efímeros sin binarios versionados.
CALDERA_NATIVE_DIR="$(mktemp -d)"
# Comentario: Limpiar temporales incluso cuando una regresión falle.
trap 'rm -rf "$CALDERA_NATIVE_DIR"' EXIT
# Comentario: Exportar el contrato real PHP que consumiría el puente ESP32.
php tests/firmware/config_fixture.php > "$CALDERA_NATIVE_DIR/config.json"
# Comentario: Compilar el modelo real con avisos estrictos y sanitizadores de memoria y UB.
g++ -std=c++17 -Wall -Wextra -Werror -fsanitize=address,undefined -fno-omit-frame-pointer -I firmware/common -I "$CALDERA_JSON_INCLUDE" tests/firmware/native_control_tests.cpp -o "$CALDERA_NATIVE_DIR/control"
# Comentario: Ejecutar los casos de frontera y las tramas corruptas contra el código compartido.
# Comentario: Permitir desactivar solo LSan en entornos con ptrace; ASan y UBSan siguen activos.
ASAN_OPTIONS="${ASAN_OPTIONS:-detect_leaks=0}" "$CALDERA_NATIVE_DIR/control" "$CALDERA_NATIVE_DIR/config.json"

# Comentario: Compilar el sketch Mega real con UART, reloj y EEPROM simulados.
g++ -std=c++17 -Wall -Wextra -Werror -fsanitize=address,undefined -fno-omit-frame-pointer -I firmware/common -I tests/firmware/stubs tests/firmware/native_mega_tests.cpp -o "$CALDERA_NATIVE_DIR/mega"
# Comentario: Ejecutar seguridad, recepción de tramas, persistencia y migración sobre el sketch real.
ASAN_OPTIONS="${ASAN_OPTIONS:-detect_leaks=0}" "$CALDERA_NATIVE_DIR/mega"

# Comentario: Inyectar cortes y corrupción contra el diario EEPROM real, sin duplicar su implementación.
g++ -std=c++17 -Wall -Wextra -Werror -fsanitize=address,undefined -fno-omit-frame-pointer -I firmware/common tests/firmware/native_storage_tests.cpp -o "$CALDERA_NATIVE_DIR/storage"
# Comentario: Comprobar cada frontera de escritura y ambos slots bajo ASan y UBSan.
ASAN_OPTIONS="${ASAN_OPTIONS:-detect_leaks=0}" "$CALDERA_NATIVE_DIR/storage"
