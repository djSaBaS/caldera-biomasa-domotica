<?php

// Comentario: Declarar tipos estrictos para consulta de comandos.
declare(strict_types=1);

// Comentario: Encapsular cola de comandos remotos.
final class CommandRepository
{
    // Comentario: Evitar instancias del repositorio.
    private function __construct()
    {
    }

    // Comentario: Obtener comandos pendientes y no expirados para un dispositivo.
    public static function pendingByDevice(PDO $connection, int $deviceId): array
    {
        // Comentario: Preparar consulta de comandos seguros pendientes.
        $statement = $connection->prepare("SELECT id, command_type, payload_json, expires_at FROM commands WHERE device_id = :device_id AND status = 'pendiente' AND (not_before_at IS NULL OR not_before_at <= NOW()) AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at ASC LIMIT 5");

        // Comentario: Ejecutar consulta con identificador interno.
        $statement->execute(['device_id' => $deviceId]);

        // Comentario: Obtener resultados como array.
        $rows = $statement->fetchAll();

        // Comentario: Normalizar estructura de comandos para firmware.
        return array_map(static function (array $row): array {
            // Comentario: Decodificar payload opcional sin romper si está vacío.
            $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);

            // Comentario: Devolver comando en formato estable.
            return [
                'id' => (int) $row['id'],
                'type' => (string) $row['command_type'],
                'payload' => is_array($payload) ? $payload : [],
                'expires_at' => $row['expires_at'],
            ];
        }, $rows ?: []);
    }

    // Comentario: Marcar comandos como enviados tras entregarlos al dispositivo.
    public static function markAsSent(PDO $connection, array $commandIds): void
    {
        // Comentario: No ejecutar SQL si no hay comandos que actualizar.
        if ($commandIds === []) {
            return;
        }

        // Comentario: Crear marcadores parametrizados para lista controlada de enteros.
        $placeholders = implode(',', array_fill(0, count($commandIds), '?'));

        // Comentario: Preparar actualización de estado.
        $statement = $connection->prepare("UPDATE commands SET status = 'enviado' WHERE id IN ({$placeholders})");

        // Comentario: Ejecutar actualización con identificadores enteros.
        $statement->execute(array_map('intval', $commandIds));
    }
}
