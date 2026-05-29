<?php

// Comentario: Declarar tipos estrictos para mantenimiento.
declare(strict_types=1);

// Comentario: Encapsular consulta y alta de mantenimientos.
final class MaintenanceRepository
{
    // Comentario: Evitar instancias del repositorio.
    private function __construct()
    {
    }

    // Comentario: Obtener últimos mantenimientos registrados.
    public static function latest(PDO $connection): array
    {
        // Comentario: Preparar consulta limitada para el panel.
        $statement = $connection->query('SELECT maintenance_type, maintenance_date, description, cost, technician, next_review_date FROM maintenance_records ORDER BY maintenance_date DESC, id DESC LIMIT 20');

        // Comentario: Devolver filas o array vacío si no hay datos.
        return $statement->fetchAll() ?: [];
    }

    // Comentario: Registrar mantenimiento validado.
    public static function insert(PDO $connection, array $data): int
    {
        // Comentario: Preparar inserción parametrizada de mantenimiento.
        $statement = $connection->prepare('INSERT INTO maintenance_records (device_id, maintenance_type, maintenance_date, description, cost, replaced_parts, technician, next_review_date, created_at, updated_at) VALUES (:device_id, :maintenance_type, :maintenance_date, :description, :cost, :replaced_parts, :technician, :next_review_date, NOW(), NOW())');

        // Comentario: Ejecutar inserción con datos validados.
        $statement->execute($data);

        // Comentario: Devolver identificador generado.
        return (int) $connection->lastInsertId();
    }
}
