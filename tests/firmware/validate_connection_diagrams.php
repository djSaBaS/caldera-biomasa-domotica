<?php

// Comentario: Declarar tipos estrictos para validación reproducible de documentación gráfica.
declare(strict_types=1);

// Comentario: Definir carpeta de diagramas de arquitectura.
$diagramDirectory = __DIR__ . '/../../docs/arquitectura/imagenes';

// Comentario: Definir diagramas obligatorios para conexiones documentadas.
$requiredDiagrams = [
    // Comentario: Exigir diagrama UART real entre placas.
    'mega_esp32_uart.svg' => ['TX1 / Pin 18', 'RX1 / Pin 19', 'RX2 / GPIO16', 'TX2 / GPIO17', 'Masa común obligatoria'],
    // Comentario: Exigir diagrama lógico de flujo hacia backend.
    'flujo_datos_firmware_backend.svg' => ['TEL:{json}', 'POST telemetría', 'GET config.php', 'CFG: parámetros validados', 'ACK:{json}'],
    // Comentario: Exigir diagrama preventivo para accesorios sin pinout real.
    'accesorios_pinout_pendiente.svg' => ['NO CABLEAR AÚN', 'Sin pines físicos definidos', 'Faltan pines reales'],
];

// Comentario: Validar cada diagrama requerido.
foreach ($requiredDiagrams as $fileName => $requiredFragments) {
    // Comentario: Construir ruta absoluta del diagrama.
    $path = $diagramDirectory . '/' . $fileName;

    // Comentario: Leer contenido del SVG.
    $contents = file_get_contents($path);

    // Comentario: Fallar si el SVG no existe o no se puede leer.
    if (!is_string($contents)) {
        // Comentario: Informar diagrama ausente.
        fwrite(STDERR, 'No se pudo leer diagrama requerido: ' . $fileName . PHP_EOL);

        // Comentario: Salir con error para CI.
        exit(1);
    }

    // Comentario: Validar que parece un SVG real y no un marcador vacío.
    if (!str_contains($contents, '<svg')) {
        // Comentario: Informar formato inválido.
        fwrite(STDERR, 'El diagrama no contiene etiqueta SVG: ' . $fileName . PHP_EOL);

        // Comentario: Salir con error para CI.
        exit(1);
    }

    // Comentario: Comprobar fragmentos obligatorios del diagrama.
    foreach ($requiredFragments as $fragment) {
        // Comentario: Continuar si el fragmento documentado existe.
        if (str_contains($contents, $fragment)) {
            continue;
        }

        // Comentario: Informar fragmento ausente.
        fwrite(STDERR, 'Falta fragmento en ' . $fileName . ': ' . $fragment . PHP_EOL);

        // Comentario: Salir con error para CI.
        exit(1);
    }
}

// Comentario: Informar validación correcta.
echo 'Diagramas de conexiones validados correctamente.' . PHP_EOL;
