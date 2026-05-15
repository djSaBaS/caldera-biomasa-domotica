#!/usr/bin/env bash
# Comentario: Activar modo estricto para detectar errores de prueba.
set -euo pipefail

# Comentario: Definir URL base configurable del backend local.
API_BASE_URL="${API_BASE_URL:-http://localhost:8081/api}"

# Comentario: Definir API key explícita, larga y distinta de placeholders documentales.
DEVICE_API_KEY="${DEVICE_API_KEY:-clave-local-pruebas-hardening-no-productiva-123456}"

# Comentario: Probar estado general de API.
curl -fsS "${API_BASE_URL}/index.php" >/tmp/caldera_api_index.json

# Comentario: Comprobar que el placeholder público se rechaza siempre.
placeholder_status=$(curl -s -o /tmp/caldera_api_placeholder.json -w '%{http_code}' -X POST "${API_BASE_URL}/telemetry.php" \
  -H 'Content-Type: application/json' \
  -H 'X-API-KEY: cambiar_en_local' \
  -d '{"device_id":"caldera-01","state":"NORMAL"}')

# Comentario: Fallar si el backend acepta el placeholder documental.
if [[ "${placeholder_status}" != "401" ]]; then
  echo "Error: el placeholder cambiar_en_local no fue rechazado." >&2
  exit 1
fi

# Comentario: Probar telemetría simulada validada.
curl -fsS -X POST "${API_BASE_URL}/telemetry.php" \
  -H 'Content-Type: application/json' \
  -H "X-API-KEY: ${DEVICE_API_KEY}" \
  -d '{"device_id":"caldera-01","state":"NORMAL","water_temp":72.4,"smoke_temp":205.2}' >/tmp/caldera_api_telemetry.json

# Comentario: Probar configuración con fallback seguro o MySQL.
curl -fsS "${API_BASE_URL}/config.php?device_id=caldera-01" \
  -H "X-API-KEY: ${DEVICE_API_KEY}" >/tmp/caldera_api_config.json

# Comentario: Probar snapshot agregado de dashboard con fallback seguro.
curl -fsS "${API_BASE_URL}/dashboard.php?device_id=caldera-01" >/tmp/caldera_api_dashboard.json

# Comentario: Probar cola de comandos segura.
curl -fsS "${API_BASE_URL}/command.php?device_id=caldera-01" \
  -H "X-API-KEY: ${DEVICE_API_KEY}" >/tmp/caldera_api_command.json


# Comentario: Probar que usuarios protegido rechaza acceso sin sesión.
users_status=$(curl -s -o /tmp/caldera_api_users_noauth.json -w '%{http_code}' "${API_BASE_URL}/users.php")

# Comentario: Fallar si usuarios permite acceso anónimo.
if [[ "${users_status}" != "401" ]]; then
  echo "Error: users.php permitió acceso sin sesión." >&2
  exit 1
fi

# Comentario: Probar que dispositivos protegido rechaza acceso sin sesión.
devices_status=$(curl -s -o /tmp/caldera_api_devices_noauth.json -w '%{http_code}' "${API_BASE_URL}/devices.php")

# Comentario: Fallar si dispositivos permite acceso anónimo.
if [[ "${devices_status}" != "401" ]]; then
  echo "Error: devices.php permitió acceso sin sesión." >&2
  exit 1
fi

# Comentario: Probar que solicitud web de comandos rechaza acceso sin sesión.
command_request_status=$(curl -s -o /tmp/caldera_api_command_request_noauth.json -w '%{http_code}' -X POST "${API_BASE_URL}/command_request.php" \
  -H 'Content-Type: application/json' \
  -d '{"device_id":"caldera-01","command_type":"STOP"}')

# Comentario: Fallar si command_request permite acceso anónimo.
if [[ "${command_request_status}" != "401" ]]; then
  echo "Error: command_request.php permitió acceso sin sesión." >&2
  exit 1
fi

# Comentario: Probar solicitud de restablecimiento sin revelar usuario.
curl -fsS -X POST "${API_BASE_URL}/password_reset_request.php" \
  -H 'Content-Type: application/json' \
  -d '{"email":"demo@example.com"}' >/tmp/caldera_api_reset.json

# Comentario: Informar finalización correcta de pruebas de humo.
echo "Pruebas de humo API completadas correctamente contra ${API_BASE_URL}"
