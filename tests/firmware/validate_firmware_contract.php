<?php

// Comentario: Declarar tipos estrictos para validación estática reproducible.
declare(strict_types=1);

// Comentario: Resolver ruta del firmware Arduino Mega.
$arduinoPath = __DIR__ . '/../../firmware/arduino-mega/src/main.ino';

// Comentario: Resolver ruta del firmware ESP32.
$esp32Path = __DIR__ . '/../../firmware/esp32/src/main.ino';

// Comentario: Leer firmware Arduino Mega.
$arduinoSource = file_get_contents($arduinoPath);

// Comentario: Leer firmware ESP32.
$esp32Source = file_get_contents($esp32Path);

// Comentario: Fallar si algún firmware no se puede leer.
if (!is_string($arduinoSource) || !is_string($esp32Source)) {
    // Comentario: Informar error de lectura al canal de error estándar.
    fwrite(STDERR, 'No se pudieron leer los firmwares Arduino/ESP32.' . PHP_EOL);

    // Comentario: Salir con error para CI.
    exit(1);
}

// Comentario: Definir requisitos mínimos del firmware Arduino.
$arduinoRequiredFragments = [
    // Comentario: Exigir EEPROM para cache offline.
    '#include <EEPROM.h>',
    // Comentario: Exigir configuración activa persistible.
    'struct BoilerConfig',
    // Comentario: Exigir carga desde EEPROM.
    'loadConfigFromEeprom',
    // Comentario: Exigir validación de rangos locales.
    'areConfigRangesSafe',
    // Comentario: Exigir trama de telemetría hacia ESP32.
    'TEL:{',
    // Comentario: Exigir ACK de configuración.
    'ACK:{',
    // Comentario: Exigir entrada de configuración desde ESP32.
    'CFG:',
];

// Comentario: Definir requisitos mínimos del firmware ESP32.
$esp32RequiredFragments = [
    // Comentario: Exigir cliente HTTP para backend.
    '#include <HTTPClient.h>',
    // Comentario: Exigir serie dedicada al Arduino.
    'Serial2.begin',
    // Comentario: Exigir envío de telemetría al backend.
    'postJson("/telemetry.php"',
    // Comentario: Exigir consulta remota de configuración.
    'getEndpoint(String("/config.php?device_id=")',
    // Comentario: Exigir envío de configuración al Arduino.
    'forwardConfigToArduino',
    // Comentario: Exigir ACK hacia backend.
    'postJson("/config_ack.php"',
];

// Comentario: Validar requisitos del firmware Arduino.
validate_fragments($arduinoSource, $arduinoRequiredFragments, 'Arduino Mega');

// Comentario: Validar requisitos del firmware ESP32.
validate_fragments($esp32Source, $esp32RequiredFragments, 'ESP32');

// Comentario: Evitar bloqueos directos por delay en firmware de control.
validate_forbidden_fragment($arduinoSource, 'delay(', 'Arduino Mega');

// Comentario: Evitar bloqueos directos por delay en firmware de conectividad.
validate_forbidden_fragment($esp32Source, 'delay(', 'ESP32');

// Comentario: Informar validación correcta.
echo 'Contrato firmware validado correctamente.' . PHP_EOL;

// Comentario: Validar que todos los fragmentos requeridos existan.
function validate_fragments(string $source, array $fragments, string $label): void
{
    // Comentario: Recorrer fragmentos obligatorios.
    foreach ($fragments as $fragment) {
        // Comentario: Continuar si el fragmento existe.
        if (str_contains($source, $fragment)) {
            continue;
        }

        // Comentario: Informar fragmento ausente.
        fwrite(STDERR, 'Falta fragmento en ' . $label . ': ' . $fragment . PHP_EOL);

        // Comentario: Salir con error para CI.
        exit(1);
    }
}

// Comentario: Validar que un fragmento prohibido no exista.
function validate_forbidden_fragment(string $source, string $fragment, string $label): void
{
    // Comentario: Salir si el fragmento prohibido no aparece.
    if (!str_contains($source, $fragment)) {
        return;
    }

    // Comentario: Informar uso prohibido.
    fwrite(STDERR, 'Fragmento prohibido en ' . $label . ': ' . $fragment . PHP_EOL);

    // Comentario: Salir con error para CI.
    exit(1);
}
