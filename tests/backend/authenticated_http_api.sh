#!/usr/bin/env bash
# Comentario: Activar modo estricto para detectar cualquier fallo HTTP autenticado.
set -euo pipefail

# Comentario: Definir URL base configurable del backend local servido por PHP embebido.
API_BASE_URL="${API_BASE_URL:-http://localhost:8081/api}"

# Comentario: Usar credenciales ficticias del seed demo cargado por mysql_integration.php.
AUTH_USER="${AUTH_USER:-demo_admin}"
AUTH_PASSWORD="${AUTH_PASSWORD:-DemoAdmin2026!NoProductiva}"

# Comentario: Crear ficheros temporales aislados para cookies y respuestas.
COOKIE_JAR="$(mktemp)"
LOGIN_BODY="$(mktemp)"
CSRF_BODY="$(mktemp)"
STATUS_BODY="$(mktemp)"

# Comentario: Limpiar temporales aunque falle la prueba.
cleanup() {
  rm -f "${COOKIE_JAR}" "${LOGIN_BODY}" "${CSRF_BODY}" "${STATUS_BODY}"
}
trap cleanup EXIT

# Comentario: Extraer un campo JSON escalar usando PHP, sin depender de jq en el runner.
json_field() {
  local file="$1"
  local field="$2"
  php -r '
    $payload = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
    $value = $payload;
    foreach (explode(".", $argv[2]) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            exit(2);
        }
        $value = $value[$segment];
    }
    if (is_bool($value)) {
        echo $value ? "true" : "false";
        exit;
    }
    if (is_scalar($value) || $value === null) {
        echo (string) $value;
        exit;
    }
    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
  ' "${file}" "${field}"
}

# Comentario: Codificar un objeto JSON simple para credenciales sin asumir caracteres seguros en Bash.
login_payload() {
  php -r 'echo json_encode(["usuario" => $argv[1], "contrasena" => $argv[2]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);' "$AUTH_USER" "$AUTH_PASSWORD"
}

# Comentario: Ejecutar curl esperando un código HTTP concreto y guardando cuerpo para diagnóstico.
expect_status() {
  local expected="$1"
  local output_file="$2"
  shift 2
  local status
  status=$(curl -sS -o "${output_file}" -w '%{http_code}' "$@")
  if [[ "${status}" != "${expected}" ]]; then
    echo "Error: se esperaba HTTP ${expected} y se recibió ${status} al llamar: $*" >&2
    cat "${output_file}" >&2 || true
    exit 1
  fi
}

# Comentario: Autenticar contra MySQL real y persistir la cookie de sesión PHP.
expect_status 200 "${LOGIN_BODY}" -c "${COOKIE_JAR}" -X POST "${API_BASE_URL}/auth_login.php" \
  -H 'Content-Type: application/json' \
  -d "$(login_payload)"

# Comentario: Verificar que el login corresponde al administrador demo esperado.
login_username="$(json_field "${LOGIN_BODY}" 'data.user.username')"
if [[ "${login_username}" != "${AUTH_USER}" ]]; then
  echo "Error: el usuario autenticado fue ${login_username}, no ${AUTH_USER}." >&2
  exit 1
fi

# Comentario: Obtener token CSRF asociado a la misma sesión autenticada.
expect_status 200 "${CSRF_BODY}" -b "${COOKIE_JAR}" "${API_BASE_URL}/csrf_token.php"
csrf_token="$(json_field "${CSRF_BODY}" 'data.csrf_token')"
if [[ -z "${csrf_token}" ]]; then
  echo 'Error: csrf_token vacío tras autenticar.' >&2
  exit 1
fi

# Comentario: Confirmar que auth_me reconoce la cookie autenticada.
expect_status 200 "${STATUS_BODY}" -b "${COOKIE_JAR}" "${API_BASE_URL}/auth_me.php"
me_username="$(json_field "${STATUS_BODY}" 'data.user.username')"
if [[ "${me_username}" != "${AUTH_USER}" ]]; then
  echo "Error: auth_me devolvió ${me_username}, no ${AUTH_USER}." >&2
  exit 1
fi

# Comentario: Validar dashboard protegido servido desde la base MySQL preparada por la integración.
expect_status 200 "${STATUS_BODY}" -b "${COOKIE_JAR}" "${API_BASE_URL}/dashboard.php?device_id=caldera-demo-01"
dashboard_persistence="$(json_field "${STATUS_BODY}" 'meta.persistence')"
if [[ "${dashboard_persistence}" != "database" ]]; then
  echo "Error: dashboard autenticado no usó MySQL, meta.persistence=${dashboard_persistence}." >&2
  exit 1
fi

# Comentario: Validar listados protegidos que requieren sesión y persistencia MySQL.
expect_status 200 "${STATUS_BODY}" -b "${COOKIE_JAR}" "${API_BASE_URL}/devices.php"
devices_persistence="$(json_field "${STATUS_BODY}" 'meta.persistence')"
if [[ "${devices_persistence}" != "database" ]]; then
  echo "Error: devices.php no usó MySQL, meta.persistence=${devices_persistence}." >&2
  exit 1
fi

expect_status 200 "${STATUS_BODY}" -b "${COOKIE_JAR}" "${API_BASE_URL}/users.php"
users_persistence="$(json_field "${STATUS_BODY}" 'meta.persistence')"
if [[ "${users_persistence}" != "database" ]]; then
  echo "Error: users.php no usó MySQL, meta.persistence=${users_persistence}." >&2
  exit 1
fi

# Comentario: Crear un comando auditable con sesión y CSRF sin habilitar encendido remoto.
expect_status 201 "${STATUS_BODY}" -b "${COOKIE_JAR}" -X POST "${API_BASE_URL}/command_request.php" \
  -H 'Content-Type: application/json' \
  -H "X-CSRF-TOKEN: ${csrf_token}" \
  -d '{"device_id":"caldera-demo-01","command_type":"STOP"}'

command_id="$(json_field "${STATUS_BODY}" 'data.command_id')"
if ! [[ "${command_id}" =~ ^[1-9][0-9]*$ ]]; then
  echo "Error: command_request.php no devolvió command_id válido: ${command_id}." >&2
  exit 1
fi

# Comentario: Confirmar que la protección CSRF bloquea operaciones mutables sin token.
expect_status 403 "${STATUS_BODY}" -b "${COOKIE_JAR}" -X POST "${API_BASE_URL}/command_request.php" \
  -H 'Content-Type: application/json' \
  -d '{"device_id":"caldera-demo-01","command_type":"STOP"}'

# Comentario: Informar finalización correcta de pruebas HTTP autenticadas.
echo "Pruebas HTTP autenticadas completadas correctamente contra ${API_BASE_URL}"
