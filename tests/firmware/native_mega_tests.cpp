// Comentario: Incluir E/S simulada y las declaraciones que Arduino genera al compilar un sketch.
#include "Arduino.h"
#include "ControlModel.h"
#include <iostream>
#include <stdexcept>
// Comentario: Adelantar tipos y funciones sin copiar su implementación de producción.
struct BoilerConfig;
enum BoilerState : uint8_t;
BoilerConfig defaultConfig();
void loadConfigFromEeprom();
bool migrateLegacyConfig();
void persistConfig(BoilerConfig);
bool isConfigValid(BoilerConfig);
bool areConfigRangesSafe(BoilerConfig);
void configToValues(const BoilerConfig&, uint32_t*);
BoilerConfig configFromValues(const uint32_t*);
uint16_t calculateConfigChecksum(BoilerConfig);
uint16_t mixChecksum(uint16_t, uint16_t);
void readEsp32Messages();
void processEsp32Line(const String&);
void applyRemoteConfig(const String&);
void sendConfigAck(bool, const char*);
const char* stateToText(BoilerState);
void readSensors(unsigned long);
void evaluateSafety();
void runStateMachine(unsigned long);
void applyOutputs(unsigned long);
void updateAugerCycle(unsigned long);
bool isCombustionState();
void forceOutputsOff();
void sendStatusToEsp32(unsigned long);
// Comentario: Ejecutar exactamente el sketch versionado, sustituyendo únicamente sus dependencias físicas.
#include "../../firmware/arduino-mega/src/main.ino"
// Comentario: Contabilizar y detener la prueba en la primera regresión real.
static unsigned checks = 0;
void check(bool condition, const char* message) {
    ++checks;
    if (!condition) { throw std::runtime_error(message); }
}
// Comentario: Construir tramas válidas desde la configuración activa del sketch real.
std::string frameFor(BoilerConfig config) {
    uint32_t values[CONFIG_FIELD_COUNT];
    configToValues(config, values);
    std::ostringstream frame;
    for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) { if (i) { frame << ';'; } frame << CONFIG_KEYS[i] << '=' << values[i]; }
    return frame.str();
}
// Comentario: Verificar EEPROM, enclavamiento y ventanas de salidas mediante el sketch compilado en el host.
int main() {
    try {
        activeConfig = defaultConfig();
        check(isConfigValid(activeConfig), "Los defaults de simulación deben validar");
        BoilerConfig candidate = activeConfig;
        candidate.configVersion = 70000;
        candidate.phaseTiming[4] = {4, 6};
        candidate.maintenancePauseMinutes = 2;
        candidate.maintenanceWorkSeconds = 10;
        applyRemoteConfig(frameFor(candidate).c_str());
        check(activeConfig.configVersion == 70000 && isConfigValid(activeConfig), "Aplicar versión de 32 bits y perfil MAN");
        const unsigned writes = EEPROM.writes;
        applyRemoteConfig(frameFor(candidate).c_str());
        check(EEPROM.writes == writes, "La repetición no debe desgastar EEPROM");
        candidate.fanPrimaryPct = 40;
        applyRemoteConfig(frameFor(candidate).c_str());
        check(activeConfig.fanPrimaryPct == 50, "No reutilizar versión con otro contenido");
        candidate.configVersion = 69999;
        applyRemoteConfig(frameFor(candidate).c_str());
        check(activeConfig.configVersion == 70000, "No aceptar retroceso de versión");
        activeConfig = defaultConfig();
        loadConfigFromEeprom();
        check(activeConfig.configVersion == 70000, "Recuperar última configuración válida sin red");
        EEPROM.bytes[0] ^= 0xFF;
        loadConfigFromEeprom();
        check(activeConfig.configVersion == 1 && isConfigValid(activeConfig), "Corrupción debe recurrir a defaults de simulación");
        // Comentario: Crear el layout AVR original de 20 bytes con su checksum histórico.
        LegacyConfigV1 legacy = {CONFIG_MAGIC, 1, 500, 12, 50, 50, 60, 75, 80, 90, 10, 30, 1, 0};
        const uint16_t fields[] = {CONFIG_MAGIC, 1, 500, 12, 50, 50, 60, 75, 80, 90, 10, 30, 1};
        for (uint16_t field : fields) { legacy.checksum = static_cast<uint16_t>(legacy.checksum * 31U + field); }
        EEPROM.put(0, legacy);
        loadConfigFromEeprom();
        check(activeConfig.configVersion == 500 && activeConfig.phaseTiming[0].onSeconds == 12 && activeConfig.maintenanceWorkSeconds == 0, "Migrar EEPROM v1 conservando consignas y corrigiendo MAN");
        check(std::string(configSource) == "eeprom_migrated", "Trazar la migración de EEPROM");
        // Comentario: Inyectar un sufijo CFG tras una línea sobredimensionada: debe descartarse entero.
        candidate = activeConfig;
        candidate.configVersion = 501;
        Serial1.outgoing.str("");
        Serial1.inject(std::string(513, 'x') + "CFG:" + frameFor(candidate) + "\n");
        readEsp32Messages();
        check(activeConfig.configVersion == 500, "No reinterpretar sufijo de trama excesiva");
        check(Serial1.outgoing.str().find("\"config_version\":0") != std::string::npos, "Una línea descartada no debe confirmar otra versión candidata");
        Serial1.inject("CFG:" + frameFor(candidate) + "\n");
        readEsp32Messages();
        check(activeConfig.configVersion == 501, "Recuperar recepción después de fin de línea");
        // Comentario: Enclavar alarmas y conservar disipación simulada ante sobretemperatura con sonda válida.
        sensors = {96, 200, 50, true, true, true};
        alarmActive = false;
        evaluateSafety();
        applyOutputs(0);
        check(alarmActive && currentState == STATE_ALT && outputs.pumpActive && !outputs.augerActive && !outputs.igniterActive, "Cortar combustible conservando bomba de disipación simulada");
        sensors.waterTempC = 70;
        evaluateSafety();
        runStateMachine(0);
        check(alarmActive && currentState == STATE_ALT, "La alarma no debe autorrearmarse");
        // Comentario: Probar NaN explícito incluso si el indicador digital de sonda sigue marcado válido.
        alarmActive = false;
        sensors.waterTempC = NAN;
        evaluateSafety();
        applyOutputs(0);
        check(alarmActive && !outputs.pumpActive, "NaN no debe eludir protección ni simular medida válida");
        // Comentario: Configurar una ventana MAN explícita y comprobar las salidas en sus fronteras.
        alarmActive = false;
        sensors = {70, 200, 50, true, true, true};
        activeConfig.phaseTiming[4] = {4, 6};
        activeConfig.maintenancePauseMinutes = 2;
        activeConfig.maintenanceWorkSeconds = 10;
        currentState = STATE_MAN;
        applyOutputs(1000);
        check(outputs.pumpActive && !outputs.augerActive && outputs.fanPrimaryPct == 0, "T04 solo mantiene bomba");
        applyOutputs(121000);
        check(!outputs.augerActive && outputs.fanPrimaryPct > 0 && !outputs.igniterActive, "Inicio T05 ventila y espera CP09");
        applyOutputs(127000);
        check(outputs.augerActive && outputs.fanPrimaryPct > 0, "Final CP09 alimenta durante CL09");
        applyOutputs(131000);
        check(!outputs.augerActive && outputs.fanPrimaryPct == 0 && outputs.pumpActive, "Fin T05 vuelve a T04");
        check(std::string(stateToText(STATE_REC)) == "REC" && std::string(stateToText(STATE_CHECK)) == "CHC", "Emitir estados canónicos");
        std::cout << "Regresiones del sketch Mega: " << checks << " comprobaciones correctas.\n";
    } catch (const std::exception& error) {
        std::cerr << "FALLO: " << error.what() << '\n';
        return 1;
    }
    return 0;
}
