<?php

// Comentario: Declarar tipos estrictos para utilidad CLI.
declare(strict_types=1);

// Comentario: Asegurar que el script se ejecuta solo desde consola.
if (PHP_SAPI !== 'cli') {
    // Comentario: Informar uso incorrecto sin revelar información del sistema.
    echo "Este script debe ejecutarse desde consola.\n";
    // Comentario: Salir con error de uso.
    exit(1);
}

// Comentario: Leer contraseña desde primer argumento CLI.
$password = (string) ($argv[1] ?? '');

// Comentario: Validar longitud mínima razonable para desarrollo.
if (strlen($password) < 12) {
    // Comentario: Explicar requisito sin imprimir la contraseña.
    echo "Uso: php tools/scripts/generar_hash_password.php 'contraseña-de-12-caracteres-o-mas'\n";
    // Comentario: Salir con error de validación.
    exit(1);
}

// Comentario: Generar hash usando algoritmo recomendado por PHP.
$hash = password_hash($password, PASSWORD_DEFAULT);

// Comentario: Imprimir únicamente el hash para copiarlo a SQL local.
echo $hash . PHP_EOL;
