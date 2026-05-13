<?php

// Comentario: Declarar tipos estrictos para acceso predecible a base de datos.
declare(strict_types=1);

// Comentario: Encapsular la conexión PDO para centralizar configuración segura.
final class Database
{
    // Comentario: Guardar una única conexión reutilizable durante la petición.
    private static ?PDO $connection = null;

    // Comentario: Evitar instancias porque la conexión se obtiene mediante método estático.
    private function __construct()
    {
    }

    // Comentario: Crear o devolver la conexión PDO configurada con excepciones.
    public static function connection(): PDO
    {
        // Comentario: Reutilizar la conexión existente si ya fue inicializada.
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // Comentario: Capturar fallos de conexión para no exponer DSN, usuario ni contraseña.
        try {
            // Comentario: Crear conexión real delegando en el constructor interno.
            self::$connection = self::createConnection();
        } catch (PDOException) {
            // Comentario: Responder error genérico sin incluir detalles sensibles del servidor.
            JsonResponse::error('error_conexion_db', 'No se pudo establecer la conexión con la base de datos.', 500);
        }

        // Comentario: Devolver conexión lista para consultas preparadas.
        return self::$connection;
    }

    // Comentario: Intentar conexión sin romper endpoints cuando la base no está importada.
    public static function tryConnection(): ?PDO
    {
        // Comentario: Reutilizar la conexión existente si ya fue inicializada.
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // Comentario: Capturar fallos de infraestructura para permitir modo degradado seguro.
        try {
            // Comentario: Crear conexión sin emitir respuesta HTTP automática.
            self::$connection = self::createConnection();
        } catch (PDOException) {
            // Comentario: Devolver nulo para que el endpoint responda en modo simulado controlado.
            return null;
        }

        // Comentario: Devolver conexión creada correctamente.
        return self::$connection;
    }

    // Comentario: Construir la conexión PDO con configuración validada fuera de Git.
    private static function createConnection(): PDO
    {
        // Comentario: Cargar host MySQL desde entorno o valor local no sensible.
        $host = getenv('DB_HOST') ?: 'localhost';

        // Comentario: Cargar puerto MySQL desde entorno o usar el estándar.
        $port = getenv('DB_PORT') ?: '3306';

        // Comentario: Cargar nombre de base de datos desde entorno.
        $name = getenv('DB_NAME') ?: 'caldera_biomasa';

        // Comentario: Cargar charset esperado para español y compatibilidad Unicode.
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        // Comentario: Cargar usuario de base de datos sin hardcodear credenciales reales.
        $user = getenv('DB_USER') ?: 'usuario_desarrollo';

        // Comentario: Cargar contraseña desde entorno sin registrar secretos ni usar valores públicos por defecto.
        $pass = getenv('DB_PASS') ?: '';

        // Comentario: Construir DSN PDO específico de MySQL sin registrarlo en logs.
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        // Comentario: Configurar PDO para errores por excepción y consultas preparadas reales.
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Comentario: Inicializar conexión con parámetros preparados y excepciones controladas por el llamador.
        return new PDO($dsn, $user, $pass, $options);
    }
}
