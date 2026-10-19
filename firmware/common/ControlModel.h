// Comentario: Evitar inclusiones múltiples del modelo portable de simulación.
#ifndef CALDERA_CONTROL_MODEL_H
#define CALDERA_CONTROL_MODEL_H

// Comentario: Usar tipos de ancho conocido tanto en AVR como en las pruebas del host.
#include <stdint.h>
// Comentario: Comparar claves de protocolo con longitud explícita.
#include <string.h>

// Comentario: Compartir el orden de campos entre el puente y el parser de Mega.
static const char* const CONFIG_KEYS[] = {"version", "auger", "fan1", "fan2", "pump", "target", "maintenance", "safety", "telemetry", "poll", "notifications", "acc_on", "acc_off", "stb_on", "stb_off", "normal_on", "normal_off", "mod_on", "mod_off", "man_on", "man_off", "man_pause", "man_work"};
// Comentario: Definir límites del proyecto sin atribuirlos al fabricante.
static const uint32_t CONFIG_MIN[] = {1, 2, 0, 0, 35, 55, 60, 75, 5, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0};
// Comentario: Mantener tiempos de fases originales en segundos salvo man_pause en minutos.
static const uint32_t CONFIG_MAX[] = {UINT32_MAX, 120, 100, 100, 75, 85, 88, 95, 300, 600, 1, 300, 300, 300, 300, 300, 300, 300, 300, 300, 300, 300, 900};
// Comentario: Derivar el tamaño del catálogo para evitar divergencias entre bucles.
static const uint8_t CONFIG_FIELD_COUNT = sizeof(CONFIG_KEYS) / sizeof(CONFIG_KEYS[0]);

// Comentario: Rechazar signos, fracciones, sufijos y desbordamientos antes de estrechar tipos.
inline bool parseUnsigned(const char* begin, uint16_t length, uint32_t& result)
{
    // Comentario: Exigir al menos un dígito decimal.
    if (length == 0) { return false; }
    // Comentario: Acumular en un candidato para no modificar la salida al fallar.
    uint32_t value = 0;
    // Comentario: Consumir todo el valor sin aceptar conversiones parciales.
    for (uint16_t i = 0; i < length; ++i) {
        // Comentario: Validar el carácter antes de convertirlo a dígito.
        if (begin[i] < '0' || begin[i] > '9') { return false; }
        // Comentario: Obtener el dígito decimal ya comprobado.
        const uint8_t digit = static_cast<uint8_t>(begin[i] - '0');
        // Comentario: Comprobar el desbordamiento antes de multiplicar.
        if (value > (UINT32_MAX - digit) / 10U) { return false; }
        // Comentario: Incorporar el dígito sin pérdida de precisión.
        value = value * 10U + digit;
    }
    // Comentario: Entregar únicamente un entero completo y representable.
    result = value;
    return true;
}

// Comentario: Validar límites compartidos y relaciones térmicas antes de aplicar configuración.
inline bool validConfigValues(const uint32_t* values)
{
    // Comentario: Comprobar todos los campos con el mismo catálogo usado por el parser.
    for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) {
        // Comentario: Rechazar cualquier campo fuera de su dominio representable.
        if (values[i] < CONFIG_MIN[i] || values[i] > CONFIG_MAX[i]) { return false; }
    }
    // Comentario: Mantener separación térmica y la ventana MAN descrita en CTP técnico p. 36.
    return values[4] < values[5] && values[5] <= values[6] && values[6] < values[7]
        && values[22] == values[19] + values[20];
}

// Comentario: Decodificar una trama completa sin claves repetidas, parciales o desconocidas.
inline bool parseConfigPayload(const char* payload, uint32_t* result)
{
    // Comentario: Acotar también la función portable aunque se invoque fuera del receptor UART.
    if (strlen(payload) > 508) { return false; }
    // Comentario: Guardar presencia y candidatos sin alterar configuración activa.
    bool seen[CONFIG_FIELD_COUNT] = {};
    uint32_t values[CONFIG_FIELD_COUNT] = {};
    // Comentario: Recorrer tokens delimitados exclusivamente por punto y coma.
    const char* token = payload;
    while (*token != '\0') {
        // Comentario: Encontrar el límite del token y su asignación.
        const char* end = strchr(token, ';');
        if (end == nullptr) { end = token + strlen(token); }
        const char* equals = static_cast<const char*>(memchr(token, '=', end - token));
        // Comentario: Rechazar tokens sin clave y valor explícitos.
        if (equals == nullptr) { return false; }
        // Comentario: Resolver la clave por coincidencia completa, nunca por subcadena.
        uint8_t field = CONFIG_FIELD_COUNT;
        for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) {
            if (strlen(CONFIG_KEYS[i]) == static_cast<size_t>(equals - token) && strncmp(token, CONFIG_KEYS[i], equals - token) == 0) { field = i; break; }
        }
        // Comentario: Rechazar claves duplicadas o fuera del contrato.
        if (field == CONFIG_FIELD_COUNT || seen[field]) { return false; }
        // Comentario: Validar todo el valor numérico antes de almacenarlo.
        if (!parseUnsigned(equals + 1, static_cast<uint16_t>(end - equals - 1), values[field])) { return false; }
        seen[field] = true;
        // Comentario: Rechazar un separador final que dejaría un token vacío.
        if (*end == ';' && end[1] == '\0') { return false; }
        token = *end == ';' ? end + 1 : end;
    }
    // Comentario: Exigir todos los campos básicos del contrato legado.
    for (uint8_t i = 0; i < 11; ++i) { if (!seen[i]) { return false; } }
    // Comentario: Detectar si el emisor proporciona el perfil completo por fases.
    bool hasPhases = false;
    for (uint8_t i = 11; i < CONFIG_FIELD_COUNT; ++i) { hasPhases = hasPhases || seen[i]; }
    // Comentario: Admitir el legado simétrico sin inventar una ventana de alimentación MAN.
    for (uint8_t i = 11; i < CONFIG_FIELD_COUNT; ++i) {
        if (hasPhases && !seen[i]) { return false; }
        if (!hasPhases) { values[i] = i < 19 ? values[1] : (i == 21 ? 1U : 0U); }
    }
    // Comentario: Validar rangos sobre tipos anchos antes de cualquier conversión AVR.
    if (!validConfigValues(values)) { return false; }
    // Comentario: Copiar el candidato únicamente tras validación atómica completa.
    memcpy(result, values, sizeof(values));
    return true;
}

// Comentario: Definir tiempos independientes de trabajo y pausa de un sinfín por fase.
struct AugerTiming { uint16_t onSeconds; uint16_t offSeconds; };

// Comentario: Calcular el ciclo comenzando por pausa; ON=0 deshabilita la alimentación.
inline bool augerAt(uint32_t elapsedMs, const AugerTiming& timing)
{
    // Comentario: Evitar módulo por cero y conservar CL=0 como ausencia de alimentación.
    if (timing.onSeconds == 0) { return false; }
    // Comentario: Calcular el periodo en 32 bits antes de multiplicar por mil.
    const uint32_t period = (static_cast<uint32_t>(timing.onSeconds) + timing.offSeconds) * 1000U;
    // Comentario: Activar únicamente la parte de trabajo posterior a la pausa independiente.
    return elapsedMs % period >= static_cast<uint32_t>(timing.offSeconds) * 1000U;
}

// Comentario: Distinguir la ventana de trabajo MAN del tiempo de espera T04 en minutos.
inline bool maintenanceWindow(uint32_t elapsedMs, uint16_t pauseMinutes, uint16_t workSeconds)
{
    // Comentario: Mantener MAN sin combustible cuando T05 es cero.
    if (workSeconds == 0) { return false; }
    // Comentario: Convertir explícitamente minutos y segundos para evitar un factor sesenta erróneo.
    const uint32_t pauseMs = static_cast<uint32_t>(pauseMinutes) * 60000U;
    const uint32_t period = pauseMs + static_cast<uint32_t>(workSeconds) * 1000U;
    // Comentario: Permitir ventilación y ciclo de carga solo durante T05.
    return elapsedMs % period >= pauseMs;
}

// Comentario: Cerrar la protección de inclusión del modelo portable.
#endif
