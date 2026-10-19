#!/usr/bin/env python3
# Comentario: Probar límites reales de HTTP, sesiones, roles y claves contra MySQL efímero.
import http.cookiejar
import json
import os
from pathlib import Path
import subprocess
import urllib.error
import urllib.request

# Comentario: Resolver servidor y ejecutor del fixture sin depender de credenciales del usuario.
BASE = os.environ.get('API_BASE_URL', 'http://127.0.0.1:8092/api')
FIXTURE = str(Path(__file__).with_name('audit_fixture.php'))
KEY = 'clave-auditoria-ci-a-ficticia-123456'
# Comentario: Guardar cookies exclusivamente en memoria para simular un navegador autenticado.
CLIENT = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
CHECKS = 0

# Comentario: Ejecutar solo operaciones cerradas del fixture contra una base terminada en _ci.
def fixture(operation):
    subprocess.run(['php', FIXTURE, operation], check=True)

# Comentario: Comparar códigos y contratos sin imprimir secretos ni cuerpos potencialmente internos.
def request(path, expected, payload=None, headers=None, raw=None):
    global CHECKS
    body = raw if raw is not None else (json.dumps(payload).encode() if payload is not None else None)
    req = urllib.request.Request(BASE + path, data=body, headers={'Content-Type': 'application/json', **(headers or {})})
    try:
        response = CLIENT.open(req, timeout=5)
    except urllib.error.HTTPError as error:
        response = error
    CHECKS += 1
    assert response.status == expected, f'{path}: esperado {expected}, recibido {response.status}'
    data = response.read()
    return json.loads(data) if data else {}

# Comentario: Limpiar restos exclusivos de pruebas anteriores antes de crear identidades sintéticas.
fixture('cleanup')
try:
    fixture('setup')
    # Comentario: Las rutas internas deben quedar fuera de la raíz publicada.
    for path in ['/../.env', '/../sql/schema.sql', '/../app/Config/config.php', '/../storage/rate-limit/info.md']:
        request(path, 404)
    # Comentario: Proteger datos y escrituras de combustible y mantenimiento sin sesión.
    for endpoint in ['fuel', 'maintenance']:
        request(f'/{endpoint}.php', 401)
        request(f'/{endpoint}.php', 401, {})
    # Comentario: Rechazar tipos incorrectos, JSON no objeto y cuerpos excesivos antes del negocio.
    request('/auth_login.php', 422, {'usuario': [], 'contrasena': 'x'})
    request('/auth_login.php', 400, raw=b'[]')
    request('/auth_login.php', 413, raw=b' ' * 16385)
    # Comentario: Autenticar con una clave vinculada únicamente al dispositivo A.
    auth = {'X-API-KEY': KEY}
    sample = {'device_id': 'auditoria-ci-a', 'state': 'CHECK', 'auger': False, 'water_temp': 70}
    request('/telemetry.php?device_id=auditoria-ci-a', 422, {**sample, 'device_id': 'auditoria-ci-b'}, auth)
    request('/telemetry.php', 401, {**sample, 'device_id': 'auditoria-ci-b'}, auth)
    request('/telemetry.php', 422, {**sample, 'auger': 'false'}, auth)
    request('/telemetry.php', 422, {**sample, 'fan_primary_pct': 306}, auth)
    request('/telemetry.php', 422, {**sample, 'water_temp': '1e999'}, auth)
    request('/telemetry.php', 422, {**sample, 'protocol_version': '9'}, auth)
    request('/telemetry.php', 422, {**sample, 'state': []}, auth)
    # Comentario: Persistir únicamente las dos muestras válidas y comprobar la normalización CHECK → CHC.
    stored = request('/telemetry.php', 202, sample, auth)
    assert stored['data']['state'] == 'CHC'
    assert stored['meta']['telemetry_id'] > 0, 'El identificador de telemetría debe ser el de la inserción'
    assert request('/telemetry.php', 202, {**sample, 'state': 'REC'}, auth)['meta']['persistence'] == 'stored'
    config = request('/config.php?device_id=auditoria-ci-a&view=firmware', 200, headers=auth)
    assert config['data']['config']['config_version'] == 70000 and 'catalog' not in config['data']
    # Comentario: Consultar el catálogo completo también debe conservar los perfiles y unidades.
    catalog = request('/config.php?device_id=auditoria-ci-a', 200, headers=auth)
    assert catalog['data']['catalog']['man_pause_minutes']['unit'] == 'min'
    # Comentario: Exigir confirmaciones correlacionables en lugar de ACK sin versión.
    request('/config_ack.php', 422, {'device_id': 'auditoria-ci-a', 'status': 'aplicada'}, auth)
    request('/config_ack.php', 202, {'device_id': 'auditoria-ci-a', 'status': 'aplicada', 'config_version': 70000}, auth)
    # Comentario: La clave de firmware no puede firmar un evento como si procediera del backend.
    event = {'device_id': 'auditoria-ci-a', 'event_type': 'sistema', 'severity': 'info', 'title': 'Origen auditado', 'origin': 'backend'}
    request('/events.php', 202, event, auth)
    request('/events.php', 422, {**event, 'title': []}, auth)
    # Comentario: Validar sesión, CSRF, bloqueo incondicional START y cola sin consumo por GET.
    login = request('/auth_login.php', 200, {'usuario': 'auditoria_ci', 'contrasena': 'AuditoriaCI2026!Ficticia'})
    csrf = {'X-CSRF-TOKEN': login['data']['csrf_token']}
    request('/command_request.php', 403, {'device_id': 'auditoria-ci-a', 'command_type': 'STOP'})
    request('/command_request.php', 403, {'device_id': 'auditoria-ci-a', 'command_type': 'START'}, csrf)
    request('/command_request.php', 201, {'device_id': 'auditoria-ci-a', 'command_type': 'STOP'}, csrf)
    request('/command.php?device_id=auditoria-ci-a', 200, headers=auth)
    request('/command.php?device_id=auditoria-ci-a', 200, headers=auth)
    # Comentario: Confirmar CSRF y validación de fecha real en compras autenticadas.
    purchase = {'fuel_type': 'pellet', 'purchase_date': '2026-02-30', 'kg_purchased': 1, 'total_price': 1}
    request('/fuel.php', 403, purchase)
    request('/fuel.php', 422, purchase, csrf)
    # Comentario: Validar campos opcionales de mantenimiento sin convertir errores en coste cero.
    maintenance = {'device_id': 'auditoria-ci-a', 'maintenance_type': 'revision', 'maintenance_date': '2026-10-19', 'description': 'Prueba aislada CI'}
    request('/maintenance.php', 422, {**maintenance, 'cost': []}, csrf)
    request('/maintenance.php', 422, {**maintenance, 'next_review_date': '2026-02-30'}, csrf)
    request('/maintenance.php', 201, maintenance, csrf)
    # Comentario: Un array no puede convertirse en un identificador de usuario para editar otro registro.
    request('/users.php', 422, {'id': [], 'name': 'CI', 'email': 'auditoria-ci@example.invalid', 'username': 'auditoria_ci', 'role': 'administrador', 'status': 'activo'}, csrf)
    # Comentario: Una muestra simulada debe anunciar su calidad en el panel.
    dashboard = request('/dashboard.php?device_id=auditoria-ci-a', 200)
    assert dashboard['data']['telemetry_status'] == 'registrada'
    assert dashboard['data']['sample_age_seconds'] >= 0
    # Comentario: Cambiar permisos después del login y exigir su aplicación a la cookie ya emitida.
    fixture('readonly')
    request('/users.php', 403)
    request('/fuel.php', 403, {**purchase, 'purchase_date': '2026-02-28'}, csrf)
    request('/fuel.php', 200)
    fixture('deactivate')
    request('/auth_me.php', 401)
    fixture('assert')
    print(f'Regresiones HTTP y MySQL: {CHECKS} peticiones verificadas.')
finally:
    # Comentario: Retirar identidades y datos sintéticos incluso si falla una aserción.
    fixture('cleanup')
