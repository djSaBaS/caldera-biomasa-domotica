<?php
// Comentario: Generar el fixture desde el exportador PHP real para detectar divergencias con C++.
declare(strict_types=1);
// Comentario: Cargar validadores sin abrir conexiones ni iniciar sesiones.
require_once __DIR__ . '/../../server/app/Core/JsonResponse.php';
// Comentario: Incorporar límites y tipos comunes del backend.
require_once __DIR__ . '/../../server/app/Core/Validation.php';
// Comentario: Usar exactamente el normalizador invocado por config.php.
require_once __DIR__ . '/../../server/app/Services/BoilerConfigValidator.php';
// Comentario: Elegir perfiles asimétricos y una versión superior a uint16 para las regresiones.
$config = array_merge(BoilerConfigValidator::defaultConfig('caldera-test'), ['config_version' => 70000, 'acc_on_seconds' => 3, 'acc_off_seconds' => 7, 'man_on_seconds' => 4, 'man_off_seconds' => 6, 'man_pause_minutes' => 2, 'man_work_seconds' => 10]);
// Comentario: Exportar el sobre compacto que recibe el parser real del ESP32.
echo json_encode(['success' => true, 'data' => ['config' => BoilerConfigValidator::normalize($config)], 'meta' => ['source' => 'database', 'protocol_version' => '1.1']], JSON_THROW_ON_ERROR);
