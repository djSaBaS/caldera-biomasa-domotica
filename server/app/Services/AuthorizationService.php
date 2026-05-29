<?php

// Comentario: Declarar tipos estrictos para autorización por roles.
declare(strict_types=1);

// Comentario: Centralizar comprobaciones de sesión y permisos.
final class AuthorizationService
{
    // Comentario: Evitar instancias porque el servicio expone métodos estáticos.
    private function __construct()
    {
    }

    // Comentario: Exigir una sesión autenticada y devolver el usuario actual.
    public static function requireAuthenticatedUser(): array
    {
        // Comentario: Iniciar sesión antes de leer identidad.
        AuthService::startSession();

        // Comentario: Obtener usuario desde sesión.
        $user = AuthService::currentUser();

        // Comentario: Rechazar si no existe usuario autenticado.
        if (!is_array($user)) {
            // Comentario: Responder sin detalles internos de sesión.
            JsonResponse::error('no_autenticado', 'Debes iniciar sesión para acceder a este recurso.', 401);
        }

        // Comentario: Devolver identidad mínima validada.
        return $user;
    }

    // Comentario: Exigir que el usuario tenga uno de los roles permitidos.
    public static function requireAnyRole(array $allowedRoles): array
    {
        // Comentario: Obtener usuario autenticado antes de validar rol.
        $user = self::requireAuthenticatedUser();

        // Comentario: Normalizar rol de sesión a cadena.
        $role = (string) ($user['role'] ?? '');

        // Comentario: Autorizar si el rol está en la lista cerrada.
        if (in_array($role, $allowedRoles, true)) {
            // Comentario: Devolver usuario autorizado para el endpoint.
            return $user;
        }

        // Comentario: Rechazar acceso sin filtrar permisos internos.
        JsonResponse::error('permiso_denegado', 'Tu rol no permite realizar esta operación.', 403);
    }
}
