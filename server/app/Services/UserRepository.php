<?php

// Comentario: Declarar tipos estrictos para repositorio de usuarios.
declare(strict_types=1);

// Comentario: Encapsular consultas de usuarios sin exponer hashes ni tokens.
final class UserRepository
{
    // Comentario: Evitar instancias del repositorio.
    private function __construct()
    {
    }

    // Comentario: Listar usuarios con rol legible y sin datos sensibles.
    public static function list(PDO $connection): array
    {
        // Comentario: Preparar consulta limitada para evitar respuestas excesivas.
        $statement = $connection->prepare('SELECT users.id, users.name, users.email, users.username, users.status, users.last_access_at, users.created_at, roles.code AS role FROM users INNER JOIN roles ON roles.id = users.role_id ORDER BY users.created_at DESC LIMIT 100');

        // Comentario: Ejecutar consulta sin parámetros externos.
        $statement->execute();

        // Comentario: Devolver filas o lista vacía.
        return $statement->fetchAll() ?: [];
    }

    // Comentario: Crear usuario con contraseña hasheada y rol validado.
    public static function create(PDO $connection, array $data): int
    {
        // Comentario: Preparar inserción usando el código de rol como entrada controlada.
        $statement = $connection->prepare('INSERT INTO users (role_id, name, email, username, password_hash, status) SELECT roles.id, :name, :email, :username, :password_hash, :status FROM roles WHERE roles.code = :role_code');

        // Comentario: Ejecutar inserción con datos validados por el endpoint.
        $statement->execute($data);

        // Comentario: Devolver identificador generado.
        return (int) $connection->lastInsertId();
    }

    // Comentario: Actualizar datos no sensibles de un usuario existente.
    public static function update(PDO $connection, int $userId, array $data): void
    {
        // Comentario: Preparar actualización parametrizada sin tocar password_hash.
        $statement = $connection->prepare('UPDATE users SET role_id = (SELECT roles.id FROM roles WHERE roles.code = :role_code LIMIT 1), name = :name, email = :email, username = :username, status = :status WHERE id = :id');

        // Comentario: Ejecutar actualización con identificador controlado.
        $statement->execute([
            'id' => $userId,
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'role_code' => $data['role_code'],
            'status' => $data['status'],
        ]);
    }
}
