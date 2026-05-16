<?php

// Comentario: Declarar tipos estrictos para limitar peticiones de forma predecible.
declare(strict_types=1);

// Comentario: Implementar limitación simple por ventana usando ficheros locales sin dependencias externas.
final class RateLimiter
{
    // Comentario: Definir subdirectorio de almacenamiento para contadores temporales.
    private const STORAGE_DIRECTORY = __DIR__ . '/../../storage/rate-limit';

    // Comentario: Evitar instancias porque la clase solo contiene operaciones estáticas.
    private function __construct()
    {
    }

    // Comentario: Exigir disponibilidad dentro del límite indicado para un ámbito funcional.
    public static function requireAllowance(string $scope, int $maxAttempts, int $windowSeconds): void
    {
        // Comentario: No aplicar límites inválidos para evitar divisiones o ventanas ambiguas.
        if ($maxAttempts < 1 || $windowSeconds < 1) {
            return;
        }

        // Comentario: Resolver identificador estable combinando ámbito y dirección cliente.
        $key = self::keyForScope($scope);

        // Comentario: Resolver ruta del contador asociado al identificador.
        $path = self::pathForKey($key);

        // Comentario: Salir en modo tolerante si no se puede preparar almacenamiento local.
        if ($path === '') {
            return;
        }

        // Comentario: Abrir fichero de contador en modo lectura/escritura con creación automática.
        $handle = fopen($path, 'c+');

        // Comentario: Salir en modo tolerante si el sistema de ficheros no permite abrir el contador.
        if ($handle === false) {
            return;
        }

        // Comentario: Bloquear el fichero para evitar carreras entre peticiones concurrentes.
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            return;
        }

        // Comentario: Leer estado actual de la ventana de rate limit.
        $state = self::readState($handle);

        // Comentario: Capturar tiempo actual una sola vez para cálculos consistentes.
        $now = time();

        // Comentario: Reiniciar ventana si no existe o ya expiró.
        if ($state['window_start'] === 0 || ($now - $state['window_start']) >= $windowSeconds) {
            $state = ['window_start' => $now, 'attempts' => 0];
        }

        // Comentario: Rechazar si ya se alcanzó el máximo permitido en la ventana.
        if ($state['attempts'] >= $maxAttempts) {
            self::closeHandle($handle);
            JsonResponse::error('rate_limit_excedido', 'Demasiadas peticiones. Inténtalo de nuevo más tarde.', 429, ['retry_after_seconds' => max(1, $windowSeconds - ($now - $state['window_start']))]);
        }

        // Comentario: Incrementar contador de intentos de la ventana actual.
        $state['attempts']++;

        // Comentario: Persistir estado actualizado de forma atómica sobre el mismo fichero.
        self::writeState($handle, $state);

        // Comentario: Liberar bloqueo y cerrar recurso de fichero.
        self::closeHandle($handle);
    }

    // Comentario: Construir clave estable incorporando IP del cliente para aislar intentos.
    private static function keyForScope(string $scope): string
    {
        // Comentario: Leer IP desde variables de servidor con valor seguro por defecto.
        $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');

        // Comentario: Hashear datos para no usar valores sensibles como nombre de fichero.
        return hash('sha256', $scope . '|' . $clientIp);
    }

    // Comentario: Resolver ruta de almacenamiento para una clave de contador.
    private static function pathForKey(string $key): string
    {
        // Comentario: Crear directorio si no existe todavía.
        if (!is_dir(self::STORAGE_DIRECTORY) && !mkdir(self::STORAGE_DIRECTORY, 0775, true)) {
            return '';
        }

        // Comentario: Rechazar almacenamiento si el directorio no es escribible.
        if (!is_writable(self::STORAGE_DIRECTORY)) {
            return '';
        }

        // Comentario: Devolver ruta del fichero JSON temporal asociado.
        return self::STORAGE_DIRECTORY . '/' . $key . '.json';
    }

    // Comentario: Leer estado JSON desde el fichero ya bloqueado.
    private static function readState($handle): array
    {
        // Comentario: Posicionar puntero al inicio antes de leer.
        rewind($handle);

        // Comentario: Leer contenido completo del fichero.
        $contents = stream_get_contents($handle);

        // Comentario: Decodificar JSON en array asociativo.
        $decoded = json_decode($contents ?: '', true);

        // Comentario: Devolver estado vacío si el fichero todavía no tiene estructura válida.
        if (!is_array($decoded)) {
            return ['window_start' => 0, 'attempts' => 0];
        }

        // Comentario: Normalizar campos numéricos para evitar tipos inesperados.
        return ['window_start' => (int) ($decoded['window_start'] ?? 0), 'attempts' => (int) ($decoded['attempts'] ?? 0)];
    }

    // Comentario: Escribir estado JSON en el fichero ya bloqueado.
    private static function writeState($handle, array $state): void
    {
        // Comentario: Truncar fichero antes de escribir el nuevo estado.
        ftruncate($handle, 0);

        // Comentario: Posicionar puntero al inicio tras truncar.
        rewind($handle);

        // Comentario: Escribir JSON compacto con estado de ventana.
        fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Comentario: Forzar volcado para reducir pérdida ante cierre abrupto.
        fflush($handle);
    }

    // Comentario: Liberar bloqueo y cerrar el recurso de fichero.
    private static function closeHandle($handle): void
    {
        // Comentario: Liberar bloqueo exclusivo del contador.
        flock($handle, LOCK_UN);

        // Comentario: Cerrar recurso abierto por fopen.
        fclose($handle);
    }
}
