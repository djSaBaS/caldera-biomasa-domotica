<?php

// Comentario: Declarar tipos estrictos para autenticación.
declare(strict_types=1);

// Comentario: Centralizar autenticación real basada en usuarios MySQL y sesiones PHP.
final class AuthService
{
    // Comentario: Evitar instancias porque el servicio mantiene métodos estáticos.
    private function __construct()
    {
    }

    // Comentario: Iniciar sesión PHP con parámetros seguros razonables para desarrollo.
    public static function startSession(): void
    {
        // Comentario: No reiniciar sesión si ya está activa.
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Comentario: Configurar cookie HTTP-only para reducir exposición ante scripts.
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);

        // Comentario: Iniciar sesión nativa de PHP.
        session_start();
    }

    // Comentario: Intentar login contra la tabla users usando password_hash/password_verify.
    public static function login(PDO $connection, string $userOrEmail, string $password): ?array
    {
        // Comentario: Preparar consulta por usuario o email activo usando placeholders únicos compatibles con prepares nativos.
        $statement = $connection->prepare("SELECT users.id, users.name, users.email, users.username, users.password_hash, users.status, roles.code AS role_code FROM users INNER JOIN roles ON roles.id = users.role_id WHERE (users.username = :username_login OR users.email = :email_login) AND users.status = 'activo' LIMIT 1");

        // Comentario: Ejecutar consulta con un valor ligado por cada placeholder declarado.
        $statement->execute(['username_login' => $userOrEmail, 'email_login' => $userOrEmail]);

        // Comentario: Obtener usuario si existe.
        $user = $statement->fetch();

        // Comentario: Rechazar login si no hay usuario activo.
        if (!is_array($user)) {
            return null;
        }

        // Comentario: Verificar contraseña usando hash seguro de PHP.
        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        // Comentario: Regenerar identificador de sesión tras autenticación correcta.
        session_regenerate_id(true);

        // Comentario: Guardar datos mínimos de usuario en sesión.
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'username' => (string) $user['username'],
            'role' => (string) $user['role_code'],
        ];

        // Comentario: Actualizar última fecha de acceso para auditoría básica.
        self::touchLastAccess($connection, (int) $user['id']);

        // Comentario: Devolver usuario autenticado sin hash de contraseña.
        return $_SESSION['user'];
    }

    // Comentario: Obtener usuario autenticado desde sesión actual.
    public static function currentUser(): ?array
    {
        // Comentario: Leer usuario de sesión si existe.
        $user = $_SESSION['user'] ?? null;

        // Comentario: Rechazar valores de sesión con formato inesperado.
        if (!is_array($user)) {
            return null;
        }

        // Comentario: Devolver datos mínimos de identidad.
        return $user;
    }

    // Comentario: Cerrar sesión actual de forma segura.
    public static function logout(): void
    {
        // Comentario: Limpiar datos de sesión en memoria.
        $_SESSION = [];

        // Comentario: Invalidar cookie si existe sesión con cookie.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        // Comentario: Destruir sesión persistida.
        session_destroy();
    }

    // Comentario: Registrar solicitud de restablecimiento sin revelar existencia de usuario.
    public static function requestPasswordReset(PDO $connection, string $email): void
    {
        // Comentario: Generar token aleatorio para envío futuro por email.
        $token = bin2hex(random_bytes(32));

        // Comentario: Hashear token para no guardarlo en claro.
        $tokenHash = hash('sha256', $token);

        // Comentario: Preparar actualización solo para usuarios activos.
        $statement = $connection->prepare("UPDATE users SET password_reset_token_hash = :token_hash, password_reset_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE), updated_at = NOW() WHERE email = :email AND status = 'activo'");

        // Comentario: Ejecutar actualización sin devolver token por seguridad.
        $statement->execute(['token_hash' => $tokenHash, 'email' => $email]);
    }

    // Comentario: Actualizar última fecha de acceso de usuario.
    private static function touchLastAccess(PDO $connection, int $userId): void
    {
        // Comentario: Preparar actualización de auditoría.
        $statement = $connection->prepare('UPDATE users SET last_access_at = NOW() WHERE id = :id');

        // Comentario: Ejecutar actualización con identificador de usuario.
        $statement->execute(['id' => $userId]);
    }
}
