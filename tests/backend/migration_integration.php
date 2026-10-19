<?php
// Comentario: Verificar la migración sobre tablas temporales que no alteran datos de la instalación.
declare(strict_types=1);
// Comentario: Limitar incluso esta prueba temporal a las bases desechables de CI.
if (!preg_match('/^[A-Za-z0-9_]+_ci$/', getenv('DB_NAME') ?: '')) {
    throw new RuntimeException('La prueba requiere una base terminada en _ci.');
}
// Comentario: Usar la conexión real y los errores uniformes del proyecto.
require_once __DIR__ . '/../../server/app/bootstrap.php';
// Comentario: Consultar el mapa de columnas del contrato exportado.
require_once __DIR__ . '/../../server/app/Services/BoilerConfigValidator.php';
$db = Database::connection();
// Comentario: Las tablas temporales tienen nombres exclusivos de la prueba y desaparecen al cerrar la conexión.
$db->exec('CREATE TEMPORARY TABLE audit_telemetry LIKE telemetry');
$db->exec('CREATE TEMPORARY TABLE audit_boiler_config LIKE boiler_config');
// Comentario: Reconstruir las diferencias relevantes del esquema 0.4.6 antes de ejecutar el SQL real.
$db->exec("ALTER TABLE audit_telemetry MODIFY boiler_state ENUM('OFF','CHECK','ACC','STB','NORMAL','MOD','MAN','SIC','SPE','ALT') NOT NULL");
foreach (array_slice(BoilerConfigValidator::wireFields(), 11) as $field) {
    // Comentario: Los nombres proceden del mapa interno, nunca de entradas HTTP.
    $db->exec('ALTER TABLE audit_boiler_config DROP COLUMN ' . $field);
}
// Comentario: Conservar filas legadas para comprobar que la migración no reescribe históricos ni versiones.
$db->exec("INSERT INTO audit_telemetry (device_id, boiler_state) VALUES (1, 'CHECK')");
$db->exec('INSERT INTO audit_boiler_config (device_id, config_version) VALUES (1, 70000)');
// Comentario: Ejecutar el mismo archivo SQL publicado para una instalación existente.
$sql = file_get_contents(__DIR__ . '/../../server/sql/migrations/0.4.7_auditoria.sql');
// Comentario: Cambiar solo el destino de las sentencias para mantener aisladas las tablas permanentes.
$sql = str_replace(['ALTER TABLE telemetry ', 'ALTER TABLE boiler_config'], ['ALTER TABLE audit_telemetry ', 'ALTER TABLE audit_boiler_config'], $sql);
foreach (explode(';', $sql) as $statement) {
    if (trim($statement) !== '') { $db->exec($statement); }
}
// Comentario: Verificar conservación y admisión de los dos estados añadidos al contrato.
$db->exec("INSERT INTO audit_telemetry (device_id, boiler_state) VALUES (1, 'CHC'), (1, 'REC')");
$states = $db->query('SELECT boiler_state FROM audit_telemetry ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
if ($states !== ['CHECK', 'CHC', 'REC']) { throw new RuntimeException('La migración altera los estados históricos.'); }
// Comentario: Verificar la versión de 32 bits y la ausencia de recetas inventadas en las columnas nuevas.
$row = $db->query('SELECT * FROM audit_boiler_config')->fetch();
if ((int) $row['config_version'] !== 70000) { throw new RuntimeException('La migración altera la versión de configuración.'); }
foreach (array_slice(BoilerConfigValidator::wireFields(), 11) as $field) {
    if (!array_key_exists($field, $row) || $row[$field] !== null) { throw new RuntimeException('La migración no conserva el perfil legado NULL.'); }
}
// Comentario: Publicar un resultado de integración sin incluir información del despliegue.
echo 'Migración SQL verificada: CHECK conservado, CHC/REC admitidos y 12 columnas nuevas NULL.' . PHP_EOL;
