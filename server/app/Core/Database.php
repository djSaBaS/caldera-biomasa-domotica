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

        // Comentario: Cargar configuración desde variables de entorno con valores seguros de desarrollo.
        $host = getenv('DB_HOST') ?: 'localhost';
        // Comentario: Cargar puerto MySQL desde entorno o usar el estándar.
        $port = getenv('DB_PORT') ?: '3306';
        // Comentario: Cargar nombre de base de datos desde entorno.
        $name = getenv('DB_NAME') ?: 'caldera_biomasa';
        // Comentario: Cargar charset esperado para español y emojis técnicos.
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
        // Comentario: Cargar usuario de base de datos sin hardcodear credenciales reales.
        $user = getenv('DB_USER') ?: 'usuario_desarrollo';
        // Comentario: Cargar contraseña desde entorno con placeholder no productivo.
        $pass = getenv('DB_PASS') ?: 'cambiar_en_local';

        // Comentario: Construir DSN PDO específico de MySQL.
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        // Comentario: Configurar PDO para errores por excepción y fetch asociativo.
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Comentario: Inicializar conexión con los parámetros preparados.
        self::$connection = new PDO($dsn, $user, $pass, $options);

        // Comentario: Devolver conexión lista para consultas preparadas.
        return self::$connection;
    }
}
