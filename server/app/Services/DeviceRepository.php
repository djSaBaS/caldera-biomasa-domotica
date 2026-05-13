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

    // Comentario: Listar dispositivos sin exponer hashes de API key.
    public static function list(PDO $connection): array
    {
        // Comentario: Preparar consulta con campos operativos no secretos.
        $statement = $connection->prepare('SELECT id, device_uid, name, location, firmware_arduino, firmware_esp32, status, last_seen_at, created_at, updated_at FROM devices ORDER BY created_at DESC LIMIT 100');

        // Comentario: Ejecutar consulta sin entrada externa.
        $statement->execute();

        // Comentario: Devolver listado o array vacío.
        return $statement->fetchAll() ?: [];
    }

    // Comentario: Crear dispositivo guardando solo hash de API key.
    public static function create(PDO $connection, array $data): int
    {
        // Comentario: Preparar inserción parametrizada de dispositivo.
        $statement = $connection->prepare('INSERT INTO devices (device_uid, name, api_key_hash, location, firmware_arduino, firmware_esp32, status) VALUES (:device_uid, :name, :api_key_hash, :location, :firmware_arduino, :firmware_esp32, :status)');

        // Comentario: Ejecutar inserción con datos validados.
        $statement->execute($data);

        // Comentario: Devolver identificador generado.
        return (int) $connection->lastInsertId();
    }

    // Comentario: Actualizar metadatos no secretos de dispositivo.
    public static function update(PDO $connection, int $deviceId, array $data): void
    {
        // Comentario: Preparar actualización sin modificar api_key_hash.
        $statement = $connection->prepare('UPDATE devices SET name = :name, location = :location, firmware_arduino = :firmware_arduino, firmware_esp32 = :firmware_esp32, status = :status WHERE id = :id');

        // Comentario: Ejecutar actualización con identificador interno.
        $statement->execute([
            'id' => $deviceId,
            'name' => $data['name'],
            'location' => $data['location'],
            'firmware_arduino' => $data['firmware_arduino'],
            'firmware_esp32' => $data['firmware_esp32'],
            'status' => $data['status'],
        ]);
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
