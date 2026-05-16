<?php

// Comentario: Declarar tipos estrictos para integración reproducible con MySQL real.
declare(strict_types=1);

// Comentario: Leer host MySQL de entorno CI o valor local.
$host = getenv('DB_HOST') ?: '127.0.0.1';

// Comentario: Leer puerto MySQL de entorno CI o valor estándar.
$port = getenv('DB_PORT') ?: '3306';

// Comentario: Leer base de datos temporal de integración.
$database = getenv('DB_NAME') ?: 'caldera_biomasa_ci';

// Comentario: Leer usuario MySQL de integración.
$user = getenv('DB_USER') ?: 'root';

// Comentario: Leer contraseña MySQL de integración.
$password = getenv('DB_PASS') ?: 'root';

// Comentario: Validar nombre de base para evitar SQL dinámico inseguro.
if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
    // Comentario: Informar configuración inválida.
    fwrite(STDERR, 'DB_NAME solo puede contener letras, números y guion bajo.' . PHP_EOL);

    // Comentario: Salir con error de integración.
    exit(1);
}

// Comentario: Construir DSN administrativo sin seleccionar base aún.
$adminDsn = "mysql:host={$host};port={$port};charset=utf8mb4";

// Comentario: Crear conexión administrativa con excepciones sin exponer credenciales ante fallos de infraestructura.
try {
    // Comentario: Inicializar PDO administrativo contra el servicio MySQL efímero.
    $adminConnection = new PDO($adminDsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $exception) {
    // Comentario: Informar fallo de conexión sin imprimir DSN, usuario ni contraseña.
    fwrite(STDERR, 'No se pudo conectar a MySQL de integración: ' . $exception->getCode() . PHP_EOL);

    // Comentario: Salir con error controlado para CI o ejecución local.
    exit(1);
}

// Comentario: Recrear base temporal para partir siempre de cero.
$adminConnection->exec('DROP DATABASE IF EXISTS `' . $database . '`');

// Comentario: Crear base temporal UTF-8 para el esquema completo.
$adminConnection->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

// Comentario: Seleccionar base temporal para ejecutar schema y seed.
$adminConnection->exec('USE `' . $database . '`');

// Comentario: Exponer variables de entorno para que Database::tryConnection use la misma base.
putenv('DB_HOST=' . $host);

// Comentario: Exponer puerto de integración al backend.
putenv('DB_PORT=' . $port);

// Comentario: Exponer nombre de base temporal al backend.
putenv('DB_NAME=' . $database);

// Comentario: Exponer usuario de integración al backend.
putenv('DB_USER=' . $user);

// Comentario: Exponer contraseña de integración al backend.
putenv('DB_PASS=' . $password);

// Comentario: Importar esquema completo del proyecto.
executeSqlFile($adminConnection, __DIR__ . '/../../server/sql/schema.sql', $database);

// Comentario: Importar datos demo sobre la base temporal.
executeSqlFile($adminConnection, __DIR__ . '/../../server/sql/seed_demo_preview.sql', $database);

// Comentario: Cargar bootstrap después de preparar entorno y base.
require_once __DIR__ . '/../../server/app/bootstrap.php';

// Comentario: Iniciar sesión antes de imprimir resultados para evitar avisos de cabeceras en CLI.
AuthService::startSession();

// Comentario: Obtener conexión desde la capa real del backend.
$connection = Database::tryConnection();

// Comentario: Verificar que el backend conecta contra MySQL real.
assert_true($connection instanceof PDO, 'Database::tryConnection devuelve PDO con MySQL efímero.');

// Comentario: Ejecutar login real con hash del seed demo.
$loggedUser = AuthService::login($connection, 'demo_admin', 'DemoAdmin2026!NoProductiva');

// Comentario: Comprobar que el usuario demo puede autenticarse.
assert_true(is_array($loggedUser), 'AuthService autentica usuario demo importado.');

// Comentario: Comprobar rol esperado del usuario demo.
assert_same('administrador', (string) ($loggedUser['role'] ?? ''), 'Usuario demo mantiene rol administrador.');

// Comentario: Construir snapshot de dashboard contra datos MySQL reales.
$snapshot = DashboardRepository::snapshot($connection, 'caldera-demo-01');

// Comentario: Comprobar que dashboard usa base de datos y no fallback.
assert_same('database', (string) ($snapshot['source'] ?? ''), 'Dashboard usa datos de MySQL efímero.');

// Comentario: Comprobar que existen KPIs suficientes para frontend.
assert_true(count($snapshot['kpis'] ?? []) >= 8, 'Dashboard MySQL expone KPIs suficientes.');

// Comentario: Comprobar que logs llegan desde eventos persistidos.
assert_true(count($snapshot['sections']['logs']['items'] ?? []) >= 1, 'Dashboard MySQL expone eventos persistidos.');

// Comentario: Comprobar que mantenimiento llega desde registros persistidos.
assert_true(count($snapshot['sections']['mantenimiento']['items'] ?? []) >= 1, 'Dashboard MySQL expone mantenimientos persistidos.');

// Comentario: Comprobar resumen de combustible contra tablas reales.
$fuelSummary = FuelRepository::summary($connection);

// Comentario: Validar que el seed demo creó compras de combustible.
assert_true((float) ($fuelSummary['kg_comprados'] ?? 0) > 0, 'FuelRepository lee compras demo desde MySQL.');

// Comentario: Conservar la base temporal para que las pruebas HTTP autenticadas reutilicen el mismo esquema y seed.
echo 'Base MySQL de integración preparada para pruebas posteriores: ' . $database . PHP_EOL;

// Comentario: Informar integración correcta.
echo 'Integración MySQL completada correctamente.' . PHP_EOL;

// Comentario: Ejecutar archivo SQL dividiéndolo en sentencias simples.
function executeSqlFile(PDO $connection, string $path, string $database): void
{
    // Comentario: Leer contenido SQL desde disco.
    $sql = file_get_contents($path);

    // Comentario: Fallar si el archivo no se puede leer.
    if (!is_string($sql)) {
        // Comentario: Informar archivo ilegible.
        throw new RuntimeException('No se pudo leer SQL: ' . $path);
    }

    // Comentario: Eliminar creación fija de base para usar la temporal de CI.
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS\s+caldera_biomasa[^;]*;/i', '', $sql) ?? $sql;

    // Comentario: Sustituir selección fija por la base temporal validada.
    $sql = preg_replace('/USE\s+caldera_biomasa\s*;/i', 'USE `' . $database . '`;', $sql) ?? $sql;

    // Comentario: Eliminar líneas de comentario para no saltar sentencias comentadas arriba.
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;

    // Comentario: Dividir por punto y coma final de sentencia simple.
    $statements = explode(';', $sql);

    // Comentario: Ejecutar cada sentencia no vacía.
    foreach ($statements as $statement) {
        // Comentario: Normalizar espacios para detectar sentencias vacías.
        $statement = trim($statement);

        // Comentario: Saltar bloques vacíos tras quitar comentarios.
        if ($statement === '') {
            continue;
        }

        // Comentario: Ejecutar sentencia reconstruyendo terminador SQL.
        $connection->exec($statement);
    }
}

// Comentario: Registrar aserción booleana para integración.
function assert_true(bool $condition, string $message): void
{
    // Comentario: Informar prueba correcta si se cumple condición.
    if ($condition) {
        echo 'OK - ' . $message . PHP_EOL;
        return;
    }

    // Comentario: Informar fallo en STDERR.
    fwrite(STDERR, 'FAIL - ' . $message . PHP_EOL);

    // Comentario: Salir con error para CI.
    exit(1);
}

// Comentario: Comparar valores esperados de forma estricta.
function assert_same(mixed $expected, mixed $actual, string $message): void
{
    // Comentario: Delegar comparación estricta en assert_true.
    assert_true($expected === $actual, $message . ' esperado=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
}
