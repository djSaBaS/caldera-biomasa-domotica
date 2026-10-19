<?php
// Comentario: Preparar identidades exclusivas de regresión sin modificar usuarios ajenos.
declare(strict_types=1);
// Comentario: Impedir ejecución sobre una base que no sea desechable de CI.
if (!preg_match('/^[A-Za-z0-9_]+_ci$/', getenv('DB_NAME') ?: '')) {
    throw new RuntimeException('Se requiere una base desechable terminada en _ci.');
}
// Comentario: Cargar la misma persistencia que utilizan los endpoints reales.
require_once __DIR__ . '/../../server/app/bootstrap.php';
// Comentario: Exigir MySQL para que la regresión compruebe persistencia efectiva.
$db = Database::connection();
// Comentario: Seleccionar únicamente operaciones cerradas de preparación y limpieza.
$operation = $argv[1] ?? '';
// Comentario: Preparar un administrador y dos dispositivos con claves diferentes.
if ($operation === 'setup') {
    // Comentario: Usar un usuario con nombre reservado a esta prueba.
    $statement = $db->prepare("INSERT INTO users (role_id, name, email, username, password_hash) SELECT id, 'Auditoría CI', 'auditoria-ci@example.invalid', 'auditoria_ci', :hash FROM roles WHERE code = 'administrador'");
    $statement->execute(['hash' => password_hash('AuditoriaCI2026!Ficticia', PASSWORD_DEFAULT)]);
    // Comentario: Crear dispositivos aislados para comprobar el límite entre sus credenciales.
    $statement = $db->prepare("INSERT INTO devices (device_uid, name, api_key_hash) VALUES (:uid, :name, :hash)");
    foreach (['a', 'b'] as $suffix) {
        $statement->execute(['uid' => 'auditoria-ci-' . $suffix, 'name' => 'Prueba CI ' . $suffix, 'hash' => password_hash('clave-auditoria-ci-' . $suffix . '-ficticia-123456', PASSWORD_DEFAULT)]);
    }
    // Comentario: Persistir configuración para el dispositivo cuya clave se usará en HTTP.
    $db->exec("INSERT INTO boiler_config (device_id, config_version) SELECT id, 70000 FROM devices WHERE device_uid = 'auditoria-ci-a'");
} elseif ($operation === 'readonly') {
    // Comentario: Modificar el rol mientras existe una cookie autenticada para verificar revocación inmediata.
    $db->exec("UPDATE users SET role_id = (SELECT id FROM roles WHERE code = 'solo_lectura') WHERE username = 'auditoria_ci'");
} elseif ($operation === 'deactivate') {
    // Comentario: Desactivar únicamente la identidad creada por esta prueba.
    $db->exec("UPDATE users SET status = 'inactivo' WHERE username = 'auditoria_ci'");
} elseif ($operation === 'assert') {
    // Comentario: Comprobar que el intento de escritura cruzada no generó muestras en B.
    $count = (int) $db->query("SELECT COUNT(*) FROM telemetry JOIN devices ON devices.id = telemetry.device_id WHERE device_uid = 'auditoria-ci-b'")->fetchColumn();
    if ($count !== 0) { throw new RuntimeException('Se escribió telemetría entre dispositivos.'); }
    // Comentario: Verificar estados canónicos y booleanos persistidos por el contrato de la API.
    $states = $db->query("SELECT boiler_state FROM telemetry JOIN devices ON devices.id = telemetry.device_id WHERE device_uid = 'auditoria-ci-a' ORDER BY telemetry.id")->fetchAll(PDO::FETCH_COLUMN);
    if ($states !== ['CHC', 'REC']) { throw new RuntimeException('Persistencia de estados canónicos incorrecta.'); }
    // Comentario: La clave de dispositivo no debe permitir suplantar el origen backend en auditoría.
    $origin = $db->query("SELECT events.origin FROM events JOIN devices ON devices.id = events.device_id WHERE device_uid = 'auditoria-ci-a' AND title = 'Origen auditado'")->fetchColumn();
    if ($origin !== 'firmware') { throw new RuntimeException('Se permitió suplantar el origen del evento.'); }
    // Comentario: Leer la cola no debe marcar como entregado el STOP que el firmware no ejecuta.
    $status = $db->query("SELECT commands.status FROM commands JOIN devices ON devices.id = commands.device_id WHERE device_uid = 'auditoria-ci-a'")->fetchColumn();
    if ($status !== 'pendiente') { throw new RuntimeException('Una lectura consumió el comando pendiente.'); }
} elseif ($operation === 'cleanup') {
    // Comentario: Borrar hijos antes que dispositivos para respetar las claves foráneas.
    foreach (['command_history' => 'command_id', 'commands' => 'device_id', 'telemetry' => 'device_id', 'maintenance_records' => 'device_id', 'events' => 'device_id', 'boiler_config' => 'device_id'] as $table => $column) {
        // Comentario: La tabla y columna proceden exclusivamente de este mapa interno cerrado.
        $ids = $table === 'command_history' ? "SELECT id FROM commands WHERE device_id IN (SELECT id FROM devices WHERE device_uid IN ('auditoria-ci-a','auditoria-ci-b'))" : "SELECT id FROM devices WHERE device_uid IN ('auditoria-ci-a','auditoria-ci-b')";
        $db->exec("DELETE FROM {$table} WHERE {$column} IN ({$ids})");
    }
    // Comentario: Retirar las identidades sintéticas tras las comprobaciones.
    $db->exec("DELETE FROM devices WHERE device_uid IN ('auditoria-ci-a','auditoria-ci-b')");
    $db->exec("DELETE FROM users WHERE username = 'auditoria_ci'");
} else {
    throw new RuntimeException('Operación de fixture desconocida.');
}
