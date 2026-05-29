<?php

// Comentario: Declarar tipos estrictos para validar el seed de forma predecible.
declare(strict_types=1);

// Comentario: Resolver ruta absoluta del seed demo dentro del repositorio.
$seedPath = __DIR__ . '/../../server/sql/seed_demo_preview.sql';

// Comentario: Leer el contenido SQL que se validará estáticamente.
$seedSql = file_get_contents($seedPath);

// Comentario: Fallar si el archivo no se puede leer.
if (!is_string($seedSql)) {
    // Comentario: Informar error claro para CI.
    fwrite(STDERR, 'No se pudo leer server/sql/seed_demo_preview.sql' . PHP_EOL);

    // Comentario: Salir con error de validación.
    exit(1);
}

// Comentario: Definir fragmentos obligatorios que aseguran una demo útil del panel.
$requiredFragments = [
    // Comentario: Exigir dispositivo demo estable.
    "'caldera-demo-01'",
    // Comentario: Exigir usuario demo documentado.
    "'demo_admin'",
    // Comentario: Exigir telemetría ficticia.
    'INSERT INTO telemetry',
    // Comentario: Exigir eventos ficticios.
    'INSERT INTO events',
    // Comentario: Exigir consumo ficticio.
    'INSERT INTO fuel_consumption',
    // Comentario: Exigir mantenimiento ficticio.
    'INSERT INTO maintenance_records',
    // Comentario: Exigir indicador de modo demo.
    "'demo_preview_enabled'",
];

// Comentario: Revisar cada fragmento obligatorio del seed.
foreach ($requiredFragments as $fragment) {
    // Comentario: Saltar fragmentos presentes.
    if (str_contains($seedSql, $fragment)) {
        // Comentario: Continuar con el siguiente requisito.
        continue;
    }

    // Comentario: Informar requisito ausente.
    fwrite(STDERR, 'Falta fragmento obligatorio en seed demo: ' . $fragment . PHP_EOL);

    // Comentario: Salir con error para bloquear CI.
    exit(1);
}

// Comentario: Evitar que el seed demo publique contraseñas productivas habituales en claro.
$forbiddenFragments = ['demoadmin2026', 'contraseña', '12345678'];

// Comentario: Comprobar fragmentos prohibidos en minúsculas para reducir falsos negativos.
$lowerSeedSql = strtolower($seedSql);

// Comentario: Revisar que no aparezcan cadenas inseguras documentales.
foreach ($forbiddenFragments as $fragment) {
    // Comentario: Saltar si el fragmento inseguro no aparece.
    if (!str_contains($lowerSeedSql, $fragment)) {
        // Comentario: Continuar con el siguiente fragmento prohibido.
        continue;
    }

    // Comentario: Informar cadena prohibida localizada.
    fwrite(STDERR, 'El seed demo contiene una cadena prohibida: ' . $fragment . PHP_EOL);

    // Comentario: Salir con error para obligar a revisar el seed.
    exit(1);
}

// Comentario: Informar validación correcta del seed demo.
echo 'Seed demo validado correctamente.' . PHP_EOL;
