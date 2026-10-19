// Comentario: Proteger la inclusión del decodificador HTTP usado por ESP32 y las pruebas nativas.
#ifndef CALDERA_CONFIG_JSON_H
#define CALDERA_CONFIG_JSON_H
// Comentario: Usar el parser JSON mantenido con comprobación de errores y tipos.
#include <ArduinoJson.h>
// Comentario: Reutilizar campos, límites y relaciones del controlador Mega.
#include "ControlModel.h"

// Comentario: Mapear las unidades explícitas de HTTP al orden de claves UART.
static const char* const HTTP_CONFIG_KEYS[] = {"config_version", "auger_cycle_seconds", "fan_primary_pct", "fan_secondary_pct", "pump_on_temp", "target_temp", "maintenance_temp", "safety_temp", "telemetry_interval_seconds", "config_poll_interval_seconds", "notifications_enabled", "acc_on_seconds", "acc_off_seconds", "stb_on_seconds", "stb_off_seconds", "normal_on_seconds", "normal_off_seconds", "mod_on_seconds", "mod_off_seconds", "man_on_seconds", "man_off_seconds", "man_pause_minutes", "man_work_seconds"};

// Comentario: Aceptar solo configuración persistida, completa y destinada a este dispositivo.
inline bool decodeConfigResponse(const char* response, size_t length, const char* device, uint32_t* result)
{
    // Comentario: Limitar el tamaño antes de reservar memoria en el documento JSON.
    if (length == 0 || length > 4096) { return false; }
    // Comentario: Acotar profundidad y rechazar sintaxis JSON incompleta o errónea.
    JsonDocument document;
    if (deserializeJson(document, response, length, DeserializationOption::NestingLimit(5))) { return false; }
    // Comentario: Verificar el sobre para no tratar errores ni fallbacks como configuración confirmada.
    if (!document["success"].is<bool>() || !document["success"].as<bool>() || document["meta"]["source"] != "database" || document["meta"]["protocol_version"] != "1.1") { return false; }
    // Comentario: Comprobar el destino dentro de data.config y no buscar claves en el catálogo.
    const JsonObjectConst config = document["data"]["config"].as<JsonObjectConst>();
    if (config.isNull() || !config["device_id"].is<const char*>() || strcmp(config["device_id"].as<const char*>(), device) != 0) { return false; }
    // Comentario: Acumular un candidato separado hasta completar todas las comprobaciones.
    uint32_t values[CONFIG_FIELD_COUNT];
    for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) {
        // Comentario: Exigir enteros representables y rechazar cadenas, signos, fracciones y campos ausentes.
        if (!config[HTTP_CONFIG_KEYS[i]].is<uint32_t>()) { return false; }
        values[i] = config[HTTP_CONFIG_KEYS[i]].as<uint32_t>();
    }
    // Comentario: Aplicar idénticos límites y relaciones térmicas que el parser UART.
    if (!validConfigValues(values)) { return false; }
    // Comentario: Entregar el candidato únicamente cuando toda la respuesta resulta válida.
    memcpy(result, values, sizeof(values));
    return true;
}
// Comentario: Cerrar la protección de inclusión.
#endif
