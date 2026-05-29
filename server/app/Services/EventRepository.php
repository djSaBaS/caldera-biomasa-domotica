<?php

// Comentario: Declarar tipos estrictos para persistencia de eventos.
declare(strict_types=1);

// Comentario: Encapsular escritura de eventos e incidencias.
final class EventRepository
{
    // Comentario: Evitar instancias del repositorio.
    private function __construct()
    {
    }

    // Comentario: Guardar un evento operativo validado.
    public static function store(PDO $connection, ?int $deviceId, array $payload): int
    {
        // Comentario: Preparar inserción de evento parametrizada.
        $statement = $connection->prepare('INSERT INTO events (device_id, event_type, severity, origin, title, message, status, created_at) VALUES (:device_id, :event_type, :severity, :origin, :title, :message, :status, NOW())');

        // Comentario: Ejecutar inserción con valores saneados.
        $statement->execute([
            'device_id' => $deviceId,
            'event_type' => (string) ($payload['event_type'] ?? 'sistema'),
            'severity' => (string) ($payload['severity'] ?? 'info'),
            'origin' => (string) ($payload['origin'] ?? 'firmware'),
            'title' => substr((string) ($payload['title'] ?? 'Evento sin título'), 0, 160),
            'message' => substr((string) ($payload['message'] ?? ''), 0, 5000),
            'status' => 'abierto',
        ]);

        // Comentario: Devolver identificador generado.
        return (int) $connection->lastInsertId();
    }
}
