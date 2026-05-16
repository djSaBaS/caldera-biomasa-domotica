<?php

// Comentario: Declarar tipos estrictos para datos de combustible.
declare(strict_types=1);

// Comentario: Encapsular compras y resumen de combustible.
final class FuelRepository
{
    // Comentario: Evitar instancias del repositorio.
    private function __construct()
    {
    }

    // Comentario: Obtener resumen básico de combustible desde MySQL.
    public static function summary(PDO $connection): array
    {
        // Comentario: Consultar total comprado histórico.
        $purchases = $connection->query('SELECT COALESCE(SUM(kg_purchased), 0) AS kg, COALESCE(SUM(total_price), 0) AS cost FROM fuel_purchases')->fetch();

        // Comentario: Consultar total consumido histórico.
        $consumption = $connection->query('SELECT COALESCE(SUM(kg_consumed), 0) AS kg, COALESCE(SUM(estimated_cost), 0) AS cost FROM fuel_consumption')->fetch();

        // Comentario: Calcular stock estimado con valores agregados.
        $stock = (float) ($purchases['kg'] ?? 0) - (float) ($consumption['kg'] ?? 0);

        // Comentario: Devolver resumen estable para panel.
        return [
            'stock_estimado_kg' => max(0, round($stock, 2)),
            'kg_comprados' => round((float) ($purchases['kg'] ?? 0), 2),
            'kg_consumidos' => round((float) ($consumption['kg'] ?? 0), 2),
            'coste_estimado_eur' => round((float) ($consumption['cost'] ?? 0), 2),
        ];
    }

    // Comentario: Registrar compra de combustible validada.
    public static function insertPurchase(PDO $connection, array $data): int
    {
        // Comentario: Preparar inserción parametrizada de compra.
        $statement = $connection->prepare('INSERT INTO fuel_purchases (fuel_type, purchase_date, kg_purchased, total_price, supplier, notes, created_at, updated_at) VALUES (:fuel_type, :purchase_date, :kg_purchased, :total_price, :supplier, :notes, NOW(), NOW())');

        // Comentario: Ejecutar inserción con datos validados previamente.
        $statement->execute($data);

        // Comentario: Devolver identificador generado.
        return (int) $connection->lastInsertId();
    }
}
