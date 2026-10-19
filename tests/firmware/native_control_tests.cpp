// Comentario: Ejecutar el mismo parser y modelo temporal que utilizan Mega y ESP32.
#include "ConfigJson.h"
// Comentario: Verificar la misma política de entrega que utiliza el puente ESP32.
#include "TelemetryFreshness.h"
// Comentario: Leer el fixture exportado por PHP sin dependencias del hardware.
#include <fstream>
// Comentario: Informar resultados de las comprobaciones conductuales.
#include <iostream>
// Comentario: Construir casos corruptos sin cambiar las funciones bajo prueba.
#include <sstream>
#include <stdexcept>
#include <string>

// Comentario: Contar comprobaciones para publicar un resultado reproducible.
static unsigned checks = 0;
// Comentario: Detener la ejecución en el primer comportamiento inesperado.
void check(bool condition, const char* message)
{
    ++checks;
    if (!condition) { throw std::runtime_error(message); }
}

// Comentario: Verificar límites temporales, rechazo atómico y compatibilidad del protocolo.
int main(int argc, char** argv)
{
    // Comentario: Exigir el fixture creado por el backend real.
    if (argc != 2) { return 2; }
    try {
        // Comentario: Una desconexión UART no debe renovar indefinidamente una muestra almacenada.
        TelemetryFreshness freshness;
        check(!freshness.ready(0) && !freshness.everReceived(), "No hay muestra antes de recibir UART");
        freshness.received(100);
        check(freshness.ready(15100) && !freshness.ready(15101), "Caducar después de quince segundos inclusive");
        check(!freshness.ready(100), "Una muestra vencida no debe resucitar tras otro rollover");
        freshness.received(UINT32_MAX - 99);
        check(freshness.ready(100) && !freshness.ready(15000), "Conservar caducidad durante rollover");
        freshness.delivered();
        check(!freshness.ready(101) && freshness.everReceived(), "No reenviar muestra ya persistida ni volver a demo");
        freshness.received(200);
        check(freshness.ready(200), "Una nueva recepción debe habilitar otra entrega");
        // Comentario: Deserializar una configuración válida con ON y OFF diferentes.
        std::ifstream input(argv[1]);
        std::string response((std::istreambuf_iterator<char>(input)), std::istreambuf_iterator<char>());
        uint32_t values[CONFIG_FIELD_COUNT] = {};
        check(decodeConfigResponse(response.c_str(), response.size(), "caldera-test", values), "El exportador PHP debe ser compatible con ESP32");
        check(values[0] == 70000 && values[11] == 3 && values[12] == 7, "La versión uint32 y las fases deben conservarse");
        check(values[19] == 4 && values[20] == 6 && values[21] == 2 && values[22] == 10, "MAN debe conservar CL09 CP09 T04 T05");
        // Comentario: Construir la trama con el mismo orden del puente para pasarla al parser Mega.
        std::ostringstream frame;
        for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) { if (i) { frame << ';'; } frame << CONFIG_KEYS[i] << '=' << values[i]; }
        const std::string valid = frame.str();
        uint32_t decoded[CONFIG_FIELD_COUNT] = {};
        check(parseConfigPayload(valid.c_str(), decoded), "Mega debe aceptar el perfil completo");
        check(memcmp(values, decoded, sizeof(values)) == 0, "La conversión HTTP UART debe ser exacta");
        // Comentario: Exigir rechazo y ausencia de escritura parcial ante campos corruptos.
        for (const std::string& invalid : {valid + ";fan1=1", valid + ";foo=2", std::string("x") + valid, valid + ";", valid.substr(0, valid.find(";man_work=")), std::string("version=4294967296") + valid.substr(valid.find(';')), std::string("version=-1") + valid.substr(valid.find(';')), std::string("version=2.5") + valid.substr(valid.find(';')), std::string("version=2x") + valid.substr(valid.find(';'))}) {
            uint32_t previous[CONFIG_FIELD_COUNT];
            memcpy(previous, decoded, sizeof(decoded));
            check(!parseConfigPayload(invalid.c_str(), decoded), "Una trama corrupta no debe aceptarse");
            check(memcmp(previous, decoded, sizeof(decoded)) == 0, "El rechazo no debe alterar el candidato previo");
        }
        // Comentario: Reproducir el estrechamiento 306 a 50 que antes eludía la validación de ventilador uint8.
        std::string wrapped = valid;
        wrapped.replace(wrapped.find("fan1=50"), 7, "fan1=306");
        check(!parseConfigPayload(wrapped.c_str(), decoded), "No debe aceptarse overflow previo a uint8");
        // Comentario: Mantener el contrato legado completo pero limitar MAN a una ventana deshabilitada.
        const std::string legacy = valid.substr(0, valid.find(";acc_on="));
        check(parseConfigPayload(legacy.c_str(), decoded) && decoded[11] == decoded[1] && decoded[19] == 0 && decoded[22] == 0, "La migración del legado no debe alimentar MAN continuamente");
        // Comentario: Comprobar el entero máximo sin signo y rechazo del desbordamiento.
        uint32_t integer = 0;
        check(parseUnsigned("4294967295", 10, integer) && integer == UINT32_MAX, "uint32 máximo debe conservarse");
        check(!parseUnsigned("4294967296", 10, integer), "Overflow uint32 debe rechazarse");
        // Comentario: Validar ciclos asimétricos justo antes, dentro y después de sus límites.
        const AugerTiming phase = {3, 7};
        check(!augerAt(6999, phase) && augerAt(7000, phase) && augerAt(9999, phase) && !augerAt(10000, phase), "Pausa y trabajo independientes deben durar 7 y 3 segundos");
        check(!augerAt(100000, {0, 0}) && augerAt(0, {3, 0}), "Los tiempos cero deben tener semántica definida");
        // Comentario: Verificar que T04 representa dos minutos y T05 diez segundos.
        check(!maintenanceWindow(119999, 2, 10) && maintenanceWindow(120000, 2, 10) && maintenanceWindow(129999, 2, 10) && !maintenanceWindow(130000, 2, 10), "MAN debe esperar T04 minutos y trabajar T05 segundos");
        check(!augerAt(5999, {4, 6}) && augerAt(6000, {4, 6}), "Durante T05, CP09 precede a CL09");
        check(!maintenanceWindow(UINT32_MAX, 1, 0), "T05 cero deshabilita MAN");
        // Comentario: Usar la resta uint32 que ejecuta Mega al desbordarse millis.
        check(static_cast<uint32_t>(uint32_t(5000) - uint32_t(UINT32_MAX - 1999)) == 7000, "La resta temporal debe tolerar rollover");
        // Comentario: Corromper el sobre JSON sin cambiar las funciones de producción.
        for (const char* field : {"device_id", "config_version", "fan_primary_pct"}) {
            JsonDocument doc;
            deserializeJson(doc, response);
            doc["data"]["config"].remove(field);
            std::string changed;
            serializeJson(doc, changed);
            check(!decodeConfigResponse(changed.c_str(), changed.size(), "caldera-test", decoded), "No deben suplirse campos obligatorios con defaults");
        }
        check(!decodeConfigResponse(response.c_str(), response.size(), "otro", decoded), "Una respuesta para otro dispositivo debe rechazarse");
        for (const char* invalid : {"{", "[]", "{\"success\":false}", "{\"config_version\":70000}"}) {
            check(!decodeConfigResponse(invalid, strlen(invalid), "caldera-test", decoded), "No debe extraerse configuración de JSON inválido o sin sobre");
        }
        // Comentario: Evitar que el fallback de MySQL sustituya una EEPROM válida.
        JsonDocument fallback;
        deserializeJson(fallback, response);
        fallback["meta"]["source"] = "fallback_safe";
        std::string fallbackText;
        serializeJson(fallback, fallbackText);
        check(!decodeConfigResponse(fallbackText.c_str(), fallbackText.size(), "caldera-test", decoded), "El fallback del servidor no debe aplicarse como remoto");
        std::cout << "Regresiones nativas de control y protocolo: " << checks << " comprobaciones correctas.\n";
    } catch (const std::exception& error) {
        std::cerr << "FALLO: " << error.what() << '\n';
        return 1;
    }
    return 0;
}
