#!/usr/bin/env bash
# Comentario: Activar modo estricto para que CI falle ante errores no controlados.
set -euo pipefail

# Comentario: Resolver raíz del repositorio desde la ubicación del script.
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

# Comentario: Entrar en la raíz para que todas las rutas sean reproducibles.
cd "${REPO_ROOT}"

# Comentario: Limpiar contadores locales de rate limit para que las pruebas sean deterministas.
find server/storage/rate-limit -name '*.json' -type f -delete 2>/dev/null || true

# Comentario: Validar sintaxis de todos los ficheros PHP del servidor, herramientas y pruebas.
find server tools tests -name '*.php' -print0 | xargs -0 -n1 php -l

# Comentario: Validar sintaxis del JavaScript principal sin instalar dependencias externas.
node --check web/assets/js/app.js

# Comentario: Validar sintaxis de scripts Bash mantenidos en el repositorio.
bash -n tests/backend/smoke_api.sh

# Comentario: Validar sintaxis del runner de calidad.
bash -n tests/backend/run_quality_checks.sh

# Comentario: Ejecutar pruebas unitarias PHP iniciales del núcleo backend.
php tests/backend/run_unit_tests.php

# Comentario: Comprobar que el seed demo contiene las entidades mínimas para previsualización.
php tests/backend/validate_demo_seed.php

# Comentario: Comprobar contrato mínimo de comunicación y cache offline del firmware.
php tests/firmware/validate_firmware_contract.php

# Comentario: Comprobar espacios finales y problemas básicos del diff de Git.
git diff --check
