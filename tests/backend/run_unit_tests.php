<?php

// Comentario: Declarar tipos estrictos para que las pruebas detecten conversiones implícitas.
declare(strict_types=1);

// Comentario: Cargar utilidades mínimas necesarias sin depender de Composer.
require_once __DIR__ . '/../../server/app/Core/JsonResponse.php';
// Comentario: Cargar lector de peticiones requerido por el validador de API key.
require_once __DIR__ . '/../../server/app/Core/Request.php';
// Comentario: Cargar limitador requerido por el validador de API key.
require_once __DIR__ . '/../../server/app/Core/RateLimiter.php';
// Comentario: Cargar conexión degradable requerida por el validador de API key.
require_once __DIR__ . '/../../server/app/Core/Database.php';
// Comentario: Cargar validador de API key para probar política de secretos.
require_once __DIR__ . '/../../server/app/Core/ApiKeyValidator.php';
// Comentario: Cargar catálogo de configuración de caldera para validar límites funcionales.
require_once __DIR__ . '/../../server/app/Services/BoilerConfigValidator.php';

// Comentario: Definir contador global de pruebas ejecutadas para resumen final.
$testsRun = 0;

// Comentario: Definir contador global de fallos para código de salida de CI.
$testsFailed = 0;

// Comentario: Registrar una aserción booleana con salida legible para humanos y CI.
function assert_true(bool $condition, string $message): void
{
    // Comentario: Usar contadores globales simples para evitar dependencias externas.
    global $testsRun, $testsFailed;

    // Comentario: Incrementar siempre el total al evaluar una aserción.
    $testsRun++;

    // Comentario: Informar prueba superada con prefijo uniforme.
    if ($condition) {
        // Comentario: Escribir resultado positivo en salida estándar.
        echo "OK - {$message}" . PHP_EOL;

        // Comentario: Finalizar esta aserción sin marcar fallo.
        return;
    }

    // Comentario: Incrementar fallos si la condición no se cumple.
    $testsFailed++;

    // Comentario: Escribir fallo en salida estándar para que GitHub Actions lo muestre.
    echo "FAIL - {$message}" . PHP_EOL;
}

// Comentario: Comparar valores estrictamente para detectar cambios de contrato.
function assert_same(mixed $expected, mixed $actual, string $message): void
{
    // Comentario: Delegar en aserción booleana usando comparación estricta.
    assert_true($expected === $actual, $message . ' esperado=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
}

// Comentario: Validar que el catálogo contiene parámetros esenciales usados por firmware y web.
$catalog = BoilerConfigValidator::catalog();

// Comentario: Comprobar existencia del modo operativo.
assert_true(isset($catalog['mode']), 'El catálogo expone el parámetro mode.');

// Comentario: Comprobar que el modo automático sigue permitido.
assert_true(in_array('automatico', $catalog['mode']['allowed'], true), 'El catálogo permite modo automatico.');

// Comentario: Comprobar límite inferior del ciclo de sinfín.
assert_same(2, $catalog['auger_cycle_seconds']['min'], 'El ciclo de sinfín conserva mínimo seguro.');

// Comentario: Comprobar límite superior de ventilador primario.
assert_same(100, $catalog['fan_primary_pct']['max'], 'El ventilador primario conserva máximo 100%.');

// Comentario: Validar configuración por defecto cuando MySQL no está disponible.
$defaultConfig = BoilerConfigValidator::defaultConfig('caldera-test-01');

// Comentario: Comprobar que la configuración por defecto referencia el dispositivo solicitado.
assert_same('caldera-test-01', $defaultConfig['device_id'], 'La configuración por defecto conserva el device_id.');

// Comentario: Comprobar que la temperatura de seguridad no queda por debajo del objetivo.
assert_true($defaultConfig['safety_temp'] >= $defaultConfig['target_temp'], 'La temperatura de seguridad por defecto es coherente.');

// Comentario: Preparar reflexión para probar la política privada de clave de entorno sin abrir HTTP real.
$environmentKeyPolicy = new ReflectionMethod(ApiKeyValidator::class, 'isValidEnvironmentKey');

// Comentario: Permitir invocar método privado de forma controlada en pruebas unitarias.
$environmentKeyPolicy->setAccessible(true);

// Comentario: Guardar valor previo de entorno para restaurarlo tras las pruebas.
$previousDeviceApiKey = getenv('DEVICE_API_KEY');

// Comentario: Configurar placeholder documental que nunca debe aceptarse.
putenv('DEVICE_API_KEY=cambiar_en_local');

// Comentario: Verificar rechazo de placeholder aunque coincida con la clave recibida.
assert_same(false, $environmentKeyPolicy->invoke(null, 'cambiar_en_local'), 'La API key placeholder se rechaza.');

// Comentario: Configurar clave demasiado corta para detectar configuraciones inseguras.
putenv('DEVICE_API_KEY=clave-corta');

// Comentario: Verificar rechazo de claves por debajo de la longitud mínima.
assert_same(false, $environmentKeyPolicy->invoke(null, 'clave-corta'), 'La API key corta se rechaza.');

// Comentario: Configurar clave larga no productiva para comparación positiva.
putenv('DEVICE_API_KEY=clave-unitaria-local-no-productiva-123456');

// Comentario: Verificar aceptación exacta de clave larga configurada por entorno.
assert_same(true, $environmentKeyPolicy->invoke(null, 'clave-unitaria-local-no-productiva-123456'), 'La API key larga configurada se acepta.');

// Comentario: Verificar rechazo de clave larga distinta para evitar falsos positivos.
assert_same(false, $environmentKeyPolicy->invoke(null, 'clave-unitaria-local-no-productiva-000000'), 'La API key distinta se rechaza.');

// Comentario: Restaurar variable de entorno previa si existía.
if ($previousDeviceApiKey !== false) {
    // Comentario: Reponer valor anterior para no contaminar procesos posteriores.
    putenv('DEVICE_API_KEY=' . $previousDeviceApiKey);
} else {
    // Comentario: Eliminar variable si no existía antes de la prueba.
    putenv('DEVICE_API_KEY');
}

// Comentario: Emitir resumen compacto de pruebas unitarias.
echo "Pruebas unitarias completadas: {$testsRun} ejecutadas, {$testsFailed} fallidas." . PHP_EOL;

// Comentario: Salir con error si alguna aserción falló.
exit($testsFailed === 0 ? 0 : 1);
