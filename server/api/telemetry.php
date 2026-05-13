<?php

// Comentario: Declarar tipos estrictos para evitar conversiones no deseadas.
declare(strict_types=1);

// Comentario: Cargar núcleo común de la API.
require_once __DIR__ . '/../app/bootstrap.php';

// Comentario: Aceptar únicamente telemetría enviada por POST.
Request::requireMethod(['POST']);

// Comentario: Exigir API key de dispositivo para telemetría.
ApiKeyValidator::requireValidDeviceKey();

// Comentario: Leer cuerpo JSON validado.
$payload = Request::jsonBody();

// Comentario: Validar identificador de dispositivo obligatorio.
$deviceId = trim((string) ($payload['device_id'] ?? ''));

// Comentario: Rechazar telemetría sin identificador de dispositivo.
if ($deviceId === '') {
    JsonResponse::error('device_id_requerido', 'El campo device_id es obligatorio.', 422);
}

// Comentario: Validar estado de caldera contra estados originales conocidos.
$state = strtoupper(trim((string) ($payload['state'] ?? 'OFF')));

// Comentario: Definir estados permitidos por la lógica original documentada.
$allowedStates = ['OFF', 'CHECK', 'ACC', 'STB', 'NORMAL', 'MOD', 'MAN', 'SIC', 'SPE', 'ALT'];

// Comentario: Rechazar estados desconocidos para no contaminar históricos.
if (!in_array($state, $allowedStates, true)) {
    JsonResponse::error('estado_invalido', 'El estado de caldera recibido no está permitido.', 422);
}

// Comentario: Responder confirmación segura sin persistencia obligatoria en esta fase.
JsonResponse::success(
    [
        'message' => 'Telemetría validada en modo base; persistencia MySQL preparada para la siguiente fase.',
        'device_id' => $deviceId,
        'state' => $state,
    ],
    [
        'simulation' => true,
    ],
    202
);
