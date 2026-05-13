<?php

// Comentario: Declarar tipos estrictos para consultas de dispositivos.
declare(strict_types=1);

// Comentario: Centralizar acceso a dispositivos registrados.
final class DeviceRepository
{
    // Comentario: Evitar instancias porque la clase solo contiene operaciones estáticas.
    private function __construct()
    {
    }

    // Comentario: Buscar identificador interno por UID lógico de dispositivo.
    public static function findIdByUid(PDO $connection, string $deviceUid): ?int
    {
        // Comentario: Preparar consulta parametrizada para evitar inyección SQL.
        $statement = $connection->prepare('SELECT id FROM devices WHERE device_uid = :device_uid AND status <> :status LIMIT 1');

        // Comentario: Ejecutar consulta usando parámetros nombrados.
        $statement->execute(['device_uid' => $deviceUid, 'status' => 'inactivo']);

        // Comentario: Obtener fila asociativa si existe.
        $row = $statement->fetch();

        // Comentario: Devolver nulo si el dispositivo no está registrado.
        if (!is_array($row)) {
            return null;
        }

        // Comentario: Convertir identificador a entero para consultas posteriores.
        return (int) $row['id'];
    }

    // Comentario: Actualizar última comunicación conocida del dispositivo.
    public static function touch(PDO $connection, int $deviceId): void
    {
        // Comentario: Preparar actualización de última comunicación.
        $statement = $connection->prepare('UPDATE devices SET last_seen_at = NOW() WHERE id = :id');

        // Comentario: Ejecutar actualización con identificador interno.
        $statement->execute(['id' => $deviceId]);
    }
}
