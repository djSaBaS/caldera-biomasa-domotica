#!/usr/bin/env bash
# Comentario: Activar modo estricto para detectar errores de prueba.
set -euo pipefail

# Comentario: Definir URL base configurable del backend local.
API_BASE_URL="${API_BASE_URL:-http://localhost:8081/api}"

# Comentario: Definir API key de desarrollo sin usar secretos reales.
DEVICE_API_KEY="${DEVICE_API_KEY:-cambiar_en_local}"

# Comentario: Probar estado general de API.
curl -fsS "${API_BASE_URL}/index.php" >/tmp/caldera_api_index.json

# Comentario: Probar telemetría simulada validada.
curl -fsS -X POST "${API_BASE_URL}/telemetry.php" \
  -H 'Content-Type: application/json' \
  -H "X-API-KEY: ${DEVICE_API_KEY}" \
  -d '{"device_id":"caldera-01","state":"NORMAL","water_temp":72.4,"smoke_temp":205.2}' >/tmp/caldera_api_telemetry.json

# Comentario: Probar configuración con fallback seguro o MySQL.
curl -fsS "${API_BASE_URL}/config.php?device_id=caldera-01" \
  -H "X-API-KEY: ${DEVICE_API_KEY}" >/tmp/caldera_api_config.json

# Comentario: Probar cola de comandos segura.
curl -fsS "${API_BASE_URL}/command.php?device_id=caldera-01" \
  -H "X-API-KEY: ${DEVICE_API_KEY}" >/tmp/caldera_api_command.json

# Comentario: Probar solicitud de restablecimiento sin revelar usuario.
curl -fsS -X POST "${API_BASE_URL}/password_reset_request.php" \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@example.com"}' >/tmp/caldera_api_reset.json

# Comentario: Informar finalización correcta de pruebas de humo.
echo "Pruebas de humo API completadas correctamente contra ${API_BASE_URL}"
