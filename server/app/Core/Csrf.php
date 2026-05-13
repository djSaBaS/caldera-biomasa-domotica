<?php

// Comentario: Declarar tipos estrictos para manejar tokens CSRF de forma predecible.
declare(strict_types=1);

// Comentario: Centralizar protección CSRF para operaciones web autenticadas.
final class Csrf
{
    // Comentario: Definir clave interna de sesión para el token CSRF.
    private const SESSION_KEY = 'csrf_token';

    // Comentario: Definir cabecera HTTP esperada en peticiones mutables.
    private const HEADER_NAME = 'X-CSRF-TOKEN';

    // Comentario: Evitar instancias porque la clase solo contiene utilidades estáticas.
    private function __construct()
    {
    }

    // Comentario: Obtener token de sesión o crearlo si todavía no existe.
    public static function token(): string
    {
        // Comentario: Asegurar que la sesión PHP está iniciada.
        AuthService::startSession();

        // Comentario: Crear token aleatorio cuando falte o tenga formato inesperado.
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            // Comentario: Guardar token con entropía suficiente para formularios web.
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        // Comentario: Devolver token asociado a la sesión actual.
        return $_SESSION[self::SESSION_KEY];
    }

    // Comentario: Exigir token válido para operaciones que modifican estado.
    public static function requireValidToken(): void
    {
        // Comentario: Obtener token esperado desde la sesión actual.
        $expectedToken = self::token();

        // Comentario: Leer token recibido desde cabecera HTTP.
        $providedToken = Request::header(self::HEADER_NAME);

        // Comentario: Rechazar peticiones sin token o con token distinto.
        if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            // Comentario: Responder error genérico sin filtrar información de sesión.
            JsonResponse::error('csrf_invalido', 'Token CSRF no válido para esta operación.', 403);
        }
    }
}
