// Comentario: Incluir EEPROM para conservar la última configuración segura sin internet.
#include <EEPROM.h>

// Comentario: Compartir parser y temporización portable con las regresiones.
#include "ControlModel.h"
// Comentario: Conservar una copia anterior válida durante cualquier escritura de configuración.
#include "ConfigJournal.h"

// Comentario: Definir versión del firmware Arduino Mega para trazabilidad con backend y telemetría.
const char* FIRMWARE_VERSION = "0.4.7-auditoria-tecnica";

// Comentario: Mantener siempre activado el modo simulación hasta validar en banco con cargas seguras.
const bool SIMULATION_MODE = true;

// Comentario: Impedir compilación para cargas físicas antes de cerrar la auditoría funcional.
static_assert(SIMULATION_MODE, "Auditoria pendiente: solo simulacion");

// Comentario: Definir identificador lógico compartido con backend y ESP32.
const char* DEVICE_ID = "caldera-01";

// Comentario: Definir puerto serie de comunicación con ESP32 en Arduino Mega.
HardwareSerial& EspLink = Serial1;

// Comentario: Definir velocidad serie estable para el puente Arduino Mega y ESP32.
const unsigned long ESP_LINK_BAUD = 115200;

// Comentario: Definir intervalo base del bucle principal sin usar delay.
const unsigned long LOOP_INTERVAL_MS = 100;

// Comentario: Definir tamaño máximo de línea recibida desde ESP32 para proteger RAM.
const unsigned int ESP_LINE_MAX_LENGTH = 512;

// Comentario: Definir firma persistente para validar contenido EEPROM propio del proyecto.
const uint16_t CONFIG_MAGIC = 0xCA1D;

// Comentario: Definir versión de estructura EEPROM para invalidar formatos antiguos de forma controlada.
const uint8_t CONFIG_SCHEMA_VERSION = 2;

// Comentario: Definir posición inicial de EEPROM para configuración persistente.
const int CONFIG_EEPROM_ADDRESS = 0;

// Comentario: Enumerar estados originales que deben respetarse.
enum BoilerState : uint8_t {
  STATE_OFF,
  STATE_CHECK,
  STATE_ACC,
  STATE_STB,
  STATE_REC,
  STATE_NORMAL,
  STATE_MOD,
  STATE_MAN,
  STATE_SIC,
  STATE_SPE,
  STATE_ALT
};

// Comentario: Agrupar sensores críticos simulados o reales futuros.
struct SensorState {
  float waterTempC;
  float smokeTempC;
  int fuelLevelPct;
  bool doorClosed;
  bool safetyThermostatOk;
  bool criticalSensorOk;
};

// Comentario: Agrupar salidas simuladas para impedir activación física accidental.
struct OutputState {
  bool augerActive;
  bool igniterActive;
  bool pumpActive;
  int fanPrimaryPct;
  int fanSecondaryPct;
};

// Comentario: Agrupar parámetros operativos recibidos desde MySQL y válidos sin internet.
struct BoilerConfig {
  uint16_t magic;
  uint8_t schemaVersion;
  uint32_t configVersion;
  uint16_t augerCycleSeconds;
  uint8_t fanPrimaryPct;
  uint8_t fanSecondaryPct;
  uint8_t pumpOnTempC;
  uint8_t targetTempC;
  uint8_t maintenanceTempC;
  uint8_t safetyTempC;
  uint16_t telemetryIntervalSeconds;
  uint16_t configPollIntervalSeconds;
  uint8_t notificationsEnabled;
  AugerTiming phaseTiming[5];
  uint16_t maintenancePauseMinutes;
  uint16_t maintenanceWorkSeconds;
  uint16_t checksum;
};

// Comentario: Mantener el layout AVR anterior para migrar EEPROM sin perder consignas válidas.
struct __attribute__((packed)) LegacyConfigV1 {
  uint16_t magic;
  uint8_t schemaVersion;
  uint16_t configVersion;
  uint16_t augerCycleSeconds;
  uint8_t fanPrimaryPct;
  uint8_t fanSecondaryPct;
  uint8_t pumpOnTempC;
  uint8_t targetTempC;
  uint8_t maintenanceTempC;
  uint8_t safetyTempC;
  uint16_t telemetryIntervalSeconds;
  uint16_t configPollIntervalSeconds;
  uint8_t notificationsEnabled;
  uint16_t checksum;
};

// Comentario: Comprobar el layout AVR histórico antes de leer EEPROM de una versión anterior.
static_assert(sizeof(LegacyConfigV1) == 20, "Layout EEPROM legado incorrecto");

// Comentario: Guardar estado actual de la caldera.
BoilerState currentState = STATE_OFF;

// Comentario: Guardar sensores simulados con valores seguros de arranque.
SensorState sensors = {72.5, 210.0, 78, true, true, true};

// Comentario: Guardar salidas simuladas inicialmente apagadas.
OutputState outputs = {false, false, false, 0, 0};

// Comentario: Guardar configuración activa que debe seguir funcionando sin internet.
BoilerConfig activeConfig;

// Comentario: Registrar última ejecución del bucle lógico.
unsigned long lastLoopMillis = 0;

// Comentario: Registrar última telemetría enviada.
unsigned long lastTelemetryMillis = 0;

// Comentario: Registrar última conmutación del sinfín simulado.
unsigned long phaseStartedMillis = 0;

// Comentario: Detectar cambios de fase para reiniciar la pausa y la ventana MAN.
BoilerState outputPhase = STATE_OFF;

// Comentario: Registrar si existe alarma activa.
bool alarmActive = false;

// Comentario: Distinguir la versión solicitada de la configuración que sigue activa tras un rechazo.
uint32_t requestedConfigVersion = 0;

// Comentario: Registrar origen de configuración para diagnóstico local y backend.
const char* configSource = "defaults";

// Comentario: Inicializar firmware en estado seguro.
void setup()
{
  // Comentario: Inicializar monitor serie USB para depuración local.
  Serial.begin(115200);

  // Comentario: Inicializar enlace dedicado con ESP32 para telemetría, configuración y ACK.
  EspLink.begin(ESP_LINK_BAUD);

  // Comentario: Cargar configuración persistida o valores conservadores por defecto.
  loadConfigFromEeprom();

  // Comentario: Registrar versión y modo de seguridad por USB.
  Serial.println("Arduino Mega iniciado con configuracion offline segura");

  // Comentario: Forzar estado inicial apagado.
  currentState = STATE_OFF;

  // Comentario: Forzar salidas apagadas al arrancar.
  forceOutputsOff();
}

// Comentario: Ejecutar bucle principal no bloqueante.
void loop()
{
  // Comentario: Capturar tiempo actual del microcontrolador.
  unsigned long currentMillis = millis();

  // Comentario: Procesar configuración entrante aunque el ciclo lógico aún no toque.
  readEsp32Messages();

  // Comentario: Evitar que la lógica principal se ejecute demasiado rápido.
  if (currentMillis - lastLoopMillis < LOOP_INTERVAL_MS) {
    return;
  }

  // Comentario: Actualizar marca temporal del bucle lógico.
  lastLoopMillis = currentMillis;

  // Comentario: Leer sensores simulados antes de evaluar seguridad.
  readSensors(currentMillis);

  // Comentario: Evaluar seguridad antes de cambiar estados o salidas.
  evaluateSafety();

  // Comentario: Ejecutar máquina de estados respetando prioridad de alarma.
  runStateMachine(currentMillis);

  // Comentario: Aplicar salidas con parámetros activos locales.
  applyOutputs(currentMillis);

  // Comentario: Enviar telemetría periódica hacia ESP32.
  sendStatusToEsp32(currentMillis);
}

// Comentario: Crear configuración conservadora que permite operar sin backend.
BoilerConfig defaultConfig()
{
  // Comentario: Preparar estructura local con límites conocidos seguros.
  BoilerConfig config = {};

  // Comentario: Definir valores del simulador, sin atribuirlos a una receta del fabricante.
  config.magic = CONFIG_MAGIC;
  config.schemaVersion = CONFIG_SCHEMA_VERSION;
  config.configVersion = 1;
  config.augerCycleSeconds = 10;
  config.fanPrimaryPct = 50;
  config.fanSecondaryPct = 50;
  config.pumpOnTempC = 60;
  config.targetTempC = 75;
  config.maintenanceTempC = 80;
  config.safetyTempC = 90;
  config.telemetryIntervalSeconds = 10;
  config.configPollIntervalSeconds = 30;
  config.notificationsEnabled = 1;
  // Comentario: Conservar preferencia simétrica en ACC/STB/NORMAL/MOD solamente como valor de proyecto.
  for (uint8_t i = 0; i < 4; ++i) { config.phaseTiming[i] = {10, 10}; }
  // Comentario: Mantener deshabilitada la carga MAN hasta configurar su perfil completo.
  config.phaseTiming[4] = {0, 0};
  config.maintenancePauseMinutes = 1;
  config.maintenanceWorkSeconds = 0;

  // Comentario: Calcular checksum antes de devolver valores por defecto.
  config.checksum = calculateConfigChecksum(config);

  // Comentario: Devolver configuración lista para uso y persistencia.
  return config;
}

// Comentario: Cargar configuración desde EEPROM con fallback seguro.
void loadConfigFromEeprom()
{
  // Comentario: Priorizar el diario con dos ranuras antes de migrar layouts legados.
  uint32_t values[CONFIG_FIELD_COUNT];
  uint32_t generation = 0;
  uint8_t slot = 0;
  if (journalLoad(EEPROM, values, generation, slot)) {
    activeConfig = configFromValues(values);
    configSource = "eeprom_journal";
    return;
  }

  // Comentario: Leer estructura completa desde EEPROM.
  EEPROM.get(CONFIG_EEPROM_ADDRESS, activeConfig);

  // Comentario: Validar firma, esquema, checksum y rangos antes de aplicar.
  if (isConfigValid(activeConfig)) {
    // Comentario: Marcar origen persistido para telemetría.
    configSource = persistConfig(activeConfig) ? "eeprom_v2_migrated" : "eeprom_v2_readonly";

    // Comentario: Salir usando la última configuración válida.
    return;
  }

  // Comentario: Intentar migración del esquema anterior antes de recurrir a valores de simulación.
  if (migrateLegacyConfig()) {
    configSource = persistConfig(activeConfig) ? "eeprom_migrated" : "eeprom_v1_readonly";
    return;
  }

  // Comentario: Aplicar valores por defecto si EEPROM está vacía o corrupta.
  activeConfig = defaultConfig();

  // Comentario: Marcar origen por defecto para diagnóstico.
  configSource = "defaults";

  // Comentario: Persistir defaults para próximos arranques autónomos.
  if (!persistConfig(activeConfig)) { configSource = "defaults_unpersisted"; }
}

// Comentario: Migrar EEPROM v1 comprobando su checksum con el algoritmo original.
bool migrateLegacyConfig()
{
  // Comentario: Leer el formato antiguo con los mismos anchos y orden de campos AVR.
  LegacyConfigV1 previous;
  EEPROM.get(CONFIG_EEPROM_ADDRESS, previous);
  if (previous.magic != CONFIG_MAGIC || previous.schemaVersion != 1) { return false; }
  // Comentario: Reconstruir los campos que participaban en el checksum anterior.
  const uint16_t fields[] = {previous.magic, previous.schemaVersion, previous.configVersion, previous.augerCycleSeconds, previous.fanPrimaryPct, previous.fanSecondaryPct, previous.pumpOnTempC, previous.targetTempC, previous.maintenanceTempC, previous.safetyTempC, previous.telemetryIntervalSeconds, previous.configPollIntervalSeconds, previous.notificationsEnabled};
  uint16_t checksum = 0;
  for (uint8_t i = 0; i < sizeof(fields) / sizeof(fields[0]); ++i) { checksum = mixChecksum(checksum, fields[i]); }
  if (checksum != previous.checksum) { return false; }
  // Comentario: Conservar consignas antiguas y expandir explícitamente el perfil simétrico legado.
  uint32_t values[CONFIG_FIELD_COUNT] = {previous.configVersion, previous.augerCycleSeconds, previous.fanPrimaryPct, previous.fanSecondaryPct, previous.pumpOnTempC, previous.targetTempC, previous.maintenanceTempC, previous.safetyTempC, previous.telemetryIntervalSeconds, previous.configPollIntervalSeconds, previous.notificationsEnabled};
  for (uint8_t i = 11; i < 19; ++i) { values[i] = previous.augerCycleSeconds; }
  // Comentario: No trasladar la alimentación continua MAN errónea del simulador anterior.
  values[21] = 1;
  if (!validConfigValues(values)) { return false; }
  activeConfig = configFromValues(values);
  return true;
}

// Comentario: Persistir configuración válida en EEPROM.
bool persistConfig(BoilerConfig config)
{
  // Comentario: Serializar campos lógicos en la ranura inactiva, conservando dirección cero para migración.
  uint32_t values[CONFIG_FIELD_COUNT];
  configToValues(config, values);
  return journalStore(EEPROM, values);
}

// Comentario: Validar estructura de configuración antes de usarla.
bool isConfigValid(BoilerConfig config)
{
  // Comentario: Rechazar si la firma no corresponde a este firmware.
  if (config.magic != CONFIG_MAGIC) {
    return false;
  }

  // Comentario: Rechazar si el esquema de datos cambió.
  if (config.schemaVersion != CONFIG_SCHEMA_VERSION) {
    return false;
  }

  // Comentario: Rechazar si el checksum no coincide.
  if (config.checksum != calculateConfigChecksum(config)) {
    return false;
  }

  // Comentario: Delegar validación funcional de rangos.
  return areConfigRangesSafe(config);
}

// Comentario: Validar rangos funcionales compatibles con backend y seguridad local.
bool areConfigRangesSafe(BoilerConfig config)
{
  // Comentario: Comparar los valores anchos con los mismos límites del parser UART.
  uint32_t values[CONFIG_FIELD_COUNT];
  configToValues(config, values);
  return config.configVersion > 0 && validConfigValues(values);
}

// Comentario: Convertir la estructura persistida a campos del contrato sin depender de padding.
void configToValues(const BoilerConfig& config, uint32_t* values)
{
  // Comentario: Escribir los once campos básicos en el orden del catálogo compartido.
  const uint32_t basic[] = {config.configVersion, config.augerCycleSeconds, config.fanPrimaryPct, config.fanSecondaryPct, config.pumpOnTempC, config.targetTempC, config.maintenanceTempC, config.safetyTempC, config.telemetryIntervalSeconds, config.configPollIntervalSeconds, config.notificationsEnabled};
  memcpy(values, basic, sizeof(basic));
  // Comentario: Incorporar pares independientes de carga y pausa para cada fase.
  for (uint8_t i = 0; i < 5; ++i) {
    values[11 + i * 2] = config.phaseTiming[i].onSeconds;
    values[12 + i * 2] = config.phaseTiming[i].offSeconds;
  }
  // Comentario: Conservar las unidades originales de la ventana MAN.
  values[21] = config.maintenancePauseMinutes;
  values[22] = config.maintenanceWorkSeconds;
}

// Comentario: Construir la estructura solo con valores ya comprobados en 32 bits.
BoilerConfig configFromValues(const uint32_t* values)
{
  // Comentario: Inicializar toda la estructura para que no queden bytes sin definir.
  BoilerConfig config = {};
  config.magic = CONFIG_MAGIC;
  config.schemaVersion = CONFIG_SCHEMA_VERSION;
  config.configVersion = values[0];
  config.augerCycleSeconds = values[1];
  config.fanPrimaryPct = values[2];
  config.fanSecondaryPct = values[3];
  config.pumpOnTempC = values[4];
  config.targetTempC = values[5];
  config.maintenanceTempC = values[6];
  config.safetyTempC = values[7];
  config.telemetryIntervalSeconds = values[8];
  config.configPollIntervalSeconds = values[9];
  config.notificationsEnabled = values[10];
  // Comentario: Materializar los perfiles por fase después de validar sus límites.
  for (uint8_t i = 0; i < 5; ++i) { config.phaseTiming[i] = {static_cast<uint16_t>(values[11 + i * 2]), static_cast<uint16_t>(values[12 + i * 2])}; }
  config.maintenancePauseMinutes = values[21];
  config.maintenanceWorkSeconds = values[22];
  // Comentario: Sellar la estructura lógica que se persistirá.
  config.checksum = calculateConfigChecksum(config);
  return config;
}

// Comentario: Calcular integridad accidental sobre campos explícitos, incluidos los 32 bits de versión.
uint16_t calculateConfigChecksum(BoilerConfig config)
{
  // Comentario: Introducir firma y versión de esquema antes de los valores operativos.
  uint16_t checksum = mixChecksum(config.magic, config.schemaVersion);
  uint32_t values[CONFIG_FIELD_COUNT];
  configToValues(config, values);
  // Comentario: Mezclar las dos mitades de cada campo sin depender de la arquitectura.
  for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) {
    checksum = mixChecksum(checksum, static_cast<uint16_t>(values[i]));
    checksum = mixChecksum(checksum, static_cast<uint16_t>(values[i] >> 16));
  }
  return checksum;
}

// Comentario: Mezclar un valor de configuración dentro del checksum.
uint16_t mixChecksum(uint16_t currentChecksum, uint16_t value)
{
  // Comentario: Aplicar mezcla determinista de bajo coste para EEPROM.
  return static_cast<uint16_t>((currentChecksum * 31U) + value);
}

// Comentario: Leer mensajes serie procedentes del ESP32 sin bloquear.
void readEsp32Messages()
{
  // Comentario: Mantener búfer estático para reconstruir líneas completas.
  static String buffer = "";

  // Comentario: Descartar hasta fin de línea después de cualquier desbordamiento.
  static bool discarding = false;

  // Comentario: Procesar todos los caracteres disponibles del enlace ESP32.
  while (EspLink.available() > 0) {
    // Comentario: Leer un carácter disponible.
    char character = static_cast<char>(EspLink.read());

    // Comentario: Procesar línea cuando llega salto de línea.
    if (discarding) {
      if (character == '\n') { discarding = false; }
      continue;
    }
    if (character == '\n') {
      // Comentario: Normalizar espacios residuales.
      buffer.trim();

      // Comentario: Procesar comando si no está vacío.
      if (buffer.length() > 0) {
        processEsp32Line(buffer);
      }

      // Comentario: Reiniciar búfer tras línea completa.
      buffer = "";
    } else if (buffer.length() < ESP_LINE_MAX_LENGTH) {
      // Comentario: Acumular carácter ordinario dentro del límite seguro.
      buffer += character;
    } else {
      // Comentario: Descartar línea excesiva para proteger memoria.
      buffer = "";
      discarding = true;

      // Comentario: La línea descartada no contiene una versión candidata verificable.
      requestedConfigVersion = 0;
      // Comentario: Enviar ACK negativo por tamaño anómalo.
      sendConfigAck(false, "linea_configuracion_demasiado_larga");
    }
  }
}

// Comentario: Procesar una línea completa procedente del ESP32.
void processEsp32Line(const String& line)
{
  // Comentario: Aceptar únicamente tramas de configuración con prefijo explícito.
  if (!line.startsWith("CFG:")) {
    return;
  }

  // Comentario: Extraer cuerpo clave-valor sin prefijo.
  String payload = line.substring(4);

  // Comentario: Intentar aplicar configuración remota validada.
  applyRemoteConfig(payload);
}

// Comentario: Aplicar configuración recibida desde backend vía ESP32.
void applyRemoteConfig(const String& payload)
{
  // Comentario: Decodificar una configuración completa en tipos anchos antes de aplicarla.
  requestedConfigVersion = 0;
  uint32_t values[CONFIG_FIELD_COUNT];
  if (!parseConfigPayload(payload.c_str(), values)) {
    sendConfigAck(false, "configuracion_invalida");
    return;
  }
  // Comentario: Registrar el candidato aunque se rechace por antigüedad.
  requestedConfigVersion = values[0];
  // Comentario: Impedir retrocesos de versión y versiones reutilizadas con contenido distinto.
  uint32_t currentValues[CONFIG_FIELD_COUNT];
  configToValues(activeConfig, currentValues);
  if (values[0] < activeConfig.configVersion || (values[0] == activeConfig.configVersion && memcmp(values, currentValues, sizeof(values)) != 0)) {
    sendConfigAck(false, "version_obsoleta_o_reutilizada");
    return;
  }
  // Comentario: No desgastar EEPROM al recibir de nuevo una configuración ya aplicada.
  if (memcmp(values, currentValues, sizeof(values)) == 0) {
    const bool stored = persistConfig(activeConfig);
    sendConfigAck(stored, stored ? "configuracion_ya_aplicada" : "persistencia_no_verificada");
    return;
  }
  // Comentario: Aplicar y persistir únicamente un candidato completamente validado.
  const BoilerConfig candidate = configFromValues(values);
  if (!persistConfig(candidate)) {
    sendConfigAck(false, "persistencia_no_verificada");
    return;
  }
  // Comentario: Activar en RAM solo después de verificar la copia durable del candidato.
  activeConfig = candidate;
  configSource = "remote_cached";
  sendConfigAck(true, "configuracion_aplicada_y_cacheada");
}

// Comentario: Enviar ACK de configuración al ESP32 para persistir evento en backend.
void sendConfigAck(bool applied, const char* message)
{
  // Comentario: Iniciar trama ACK diferenciada de telemetría.
  EspLink.print("ACK:{\"device_id\":\"");

  // Comentario: Añadir identificador del dispositivo.
  EspLink.print(DEVICE_ID);

  // Comentario: Añadir estado normalizado requerido por backend.
  EspLink.print("\",\"status\":\"");

  // Comentario: Añadir estado aplicada o rechazada.
  EspLink.print(applied ? "aplicada" : "rechazada");

  // Comentario: Añadir versión de configuración activa.
  EspLink.print("\",\"config_version\":");

  // Comentario: Escribir versión como número JSON.
  EspLink.print(requestedConfigVersion);

  // Comentario: Informar por separado la versión que continúa activa y el alcance parcial del ACK.
  EspLink.print(",\"active_config_version\":");
  EspLink.print(activeConfig.configVersion);
  EspLink.print(",\"scope\":\"control_subset\",\"protocol_version\":\"1.1\"");

  // Comentario: Añadir mensaje diagnóstico corto.
  EspLink.print(",\"message\":\"");

  // Comentario: Escribir mensaje controlado sin datos sensibles.
  EspLink.print(message);

  // Comentario: Cerrar JSON y línea.
  EspLink.println("\"}");
}

// Comentario: Convertir estado enumerado a texto original.
const char* stateToText(BoilerState state)
{
  // Comentario: Seleccionar texto estable para API y logs.
  switch (state) {
    case STATE_OFF: return "OFF";
    case STATE_CHECK: return "CHC";
    case STATE_ACC: return "ACC";
    case STATE_STB: return "STB";
    case STATE_REC: return "REC";
    case STATE_NORMAL: return "NORMAL";
    case STATE_MOD: return "MOD";
    case STATE_MAN: return "MAN";
    case STATE_SIC: return "SIC";
    case STATE_SPE: return "SPE";
    case STATE_ALT: return "ALT";
    default: return "ALT";
  }
}

// Comentario: Simular lectura de sensores sin hardware real.
void readSensors(unsigned long currentMillis)
{
  // Comentario: Calcular oscilación suave para pruebas visuales.
  float simulatedWave = (currentMillis % 60000UL) / 60000.0;

  // Comentario: Mantener temperatura de agua alrededor del objetivo activo.
  sensors.waterTempC = (activeConfig.targetTempC - 6.0) + (simulatedWave * 8.0);

  // Comentario: Mantener temperatura de humos dentro de rango operativo simulado.
  sensors.smokeTempC = 170.0 + (simulatedWave * 70.0);

  // Comentario: Mantener nivel de combustible decreciente simulado sin llegar a cero.
  sensors.fuelLevelPct = 78;
}

// Comentario: Evaluar reglas críticas de seguridad local.
void evaluateSafety()
{
  // Comentario: Detectar fallo de sensor crítico.
  bool sensorFailure = !sensors.criticalSensorOk || !isfinite(sensors.waterTempC) || !isfinite(sensors.smokeTempC);

  // Comentario: Detectar sobretemperatura usando configuración local cacheada.
  bool overTemperature = sensors.waterTempC >= activeConfig.safetyTempC;

  // Comentario: Detectar puerta abierta o termostato de seguridad disparado.
  bool safetyChainOpen = !sensors.doorClosed || !sensors.safetyThermostatOk;

  // Comentario: Activar alarma si cualquier condición crítica aparece.
  alarmActive = alarmActive || sensorFailure || overTemperature || safetyChainOpen;

  // Comentario: Pasar a ALT si existe alarma activa.
  if (alarmActive) {
    currentState = STATE_ALT;
  }
}

// Comentario: Ejecutar máquina de estados inicial sin inventar combustión nueva.
void runStateMachine(unsigned long currentMillis)
{
  // Comentario: Ignorar temporizador si existe alarma activa.
  if (alarmActive) {
    return;
  }

  // Comentario: Simular avance de estados solo para panel y pruebas.
  unsigned long phase = (currentMillis / 15000UL) % 5UL;

  // Comentario: Seleccionar fase simulada sin activar hardware real.
  if (phase == 0UL) {
    currentState = STATE_OFF;
  } else if (phase == 1UL) {
    currentState = STATE_CHECK;
  } else if (phase == 2UL) {
    currentState = STATE_ACC;
  } else if (phase == 3UL) {
    currentState = STATE_STB;
  } else {
    currentState = STATE_NORMAL;
  }
}

// Comentario: Aplicar salidas simuladas sin energizar pines físicos.
void applyOutputs(unsigned long currentMillis)
{
  // Comentario: Reiniciar temporización al entrar en otra fase, sin heredar un pulso activo.
  if (currentState != outputPhase) {
    phaseStartedMillis = currentMillis;
    outputPhase = currentState;
  }
  // Comentario: Cortar combustión ante alarma sin suprimir automáticamente la disipación de calor.
  if (alarmActive) {
    forceOutputsOff();
    // Comentario: Con sonda válida, representar la bomba de disipación; no se escriben pines.
    outputs.pumpActive = sensors.criticalSensorOk && isfinite(sensors.waterTempC) && sensors.waterTempC >= activeConfig.pumpOnTempC;
    return;
  }
  // Comentario: Mantener bomba independiente de la ventana de combustible MAN.
  outputs.pumpActive = sensors.waterTempC >= activeConfig.pumpOnTempC;
  // Comentario: Limitar MAN a su ventana T05 después de esperar T04 minutos.
  const bool inWindow = currentState != STATE_MAN || maintenanceWindow(static_cast<uint32_t>(currentMillis - phaseStartedMillis), activeConfig.maintenancePauseMinutes, activeConfig.maintenanceWorkSeconds);
  // Comentario: Aplicar ventilación simulada únicamente durante fases y ventanas admitidas.
  outputs.fanPrimaryPct = isCombustionState() && inWindow ? activeConfig.fanPrimaryPct : 0;
  outputs.fanSecondaryPct = isCombustionState() && inWindow ? activeConfig.fanSecondaryPct : 0;
  // Comentario: No extender la ignición a MAN ni a otras fases no verificadas.
  outputs.igniterActive = currentState == STATE_ACC;
  // Comentario: Evaluar tiempos independientes de carga y pausa sin activar GPIO.
  updateAugerCycle(currentMillis);
}

// Comentario: Seleccionar el perfil de sinfín de cada fase sin imponer ON=OFF.
void updateAugerCycle(unsigned long currentMillis)
{
  // Comentario: Mantener alimentación cortada fuera de combustión o con alarma enclavada.
  outputs.augerActive = false;
  if (!isCombustionState() || alarmActive) { return; }
  // Comentario: Resolver el índice estable de ACC, STB, NORMAL, MOD o MAN.
  const uint8_t index = currentState == STATE_ACC ? 0 : currentState == STATE_STB ? 1 : currentState == STATE_NORMAL ? 2 : currentState == STATE_MOD ? 3 : 4;
  // Comentario: Usar resta sin signo para tolerar el desbordamiento de millis.
  uint32_t elapsed = static_cast<uint32_t>(currentMillis - phaseStartedMillis);
  // Comentario: En MAN, la pausa CP09 pertenece a T05 y no al periodo externo T04.
  if (currentState == STATE_MAN) {
    if (!maintenanceWindow(elapsed, activeConfig.maintenancePauseMinutes, activeConfig.maintenanceWorkSeconds)) { return; }
    const uint32_t pauseMs = static_cast<uint32_t>(activeConfig.maintenancePauseMinutes) * 60000U;
    elapsed = elapsed % (pauseMs + static_cast<uint32_t>(activeConfig.maintenanceWorkSeconds) * 1000U) - pauseMs;
  }
  // Comentario: Calcular la salida desde el inicio de fase para evitar deriva por bucles tardíos.
  outputs.augerActive = augerAt(elapsed, activeConfig.phaseTiming[index]);
}

// Comentario: Determinar si el estado permite combustión simulada.
bool isCombustionState()
{
  // Comentario: Permitir simulación en ACC, STB, NORMAL, MOD y MAN.
  return currentState == STATE_ACC || currentState == STATE_STB || currentState == STATE_NORMAL || currentState == STATE_MOD || currentState == STATE_MAN;
}

// Comentario: Forzar todas las salidas simuladas a estado apagado.
void forceOutputsOff()
{
  // Comentario: Desactivar sinfín.
  outputs.augerActive = false;

  // Comentario: Desactivar bujía.
  outputs.igniterActive = false;

  // Comentario: Desactivar bomba.
  outputs.pumpActive = false;

  // Comentario: Desactivar ventilador primario.
  outputs.fanPrimaryPct = 0;

  // Comentario: Desactivar ventilador secundario.
  outputs.fanSecondaryPct = 0;
}

// Comentario: Enviar estado JSON compacto hacia ESP32 por enlace dedicado.
void sendStatusToEsp32(unsigned long currentMillis)
{
  // Comentario: Convertir intervalo de telemetría configurado a milisegundos.
  unsigned long telemetryIntervalMs = static_cast<unsigned long>(activeConfig.telemetryIntervalSeconds) * 1000UL;

  // Comentario: Respetar intervalo de telemetría para no saturar comunicación serie.
  if (currentMillis - lastTelemetryMillis < telemetryIntervalMs) {
    return;
  }

  // Comentario: Actualizar marca temporal de último envío.
  lastTelemetryMillis = currentMillis;

  // Comentario: Construir telemetría JSON manual simple para evitar dependencias.
  EspLink.print("TEL:{\"device_id\":\"");

  // Comentario: Añadir identificador lógico del dispositivo.
  EspLink.print(DEVICE_ID);

  // Comentario: Añadir firmware Arduino para trazabilidad.
  EspLink.print("\",\"firmware_arduino\":\"");

  // Comentario: Escribir versión del firmware Arduino.
  EspLink.print(FIRMWARE_VERSION);

  // Comentario: Añadir indicador de simulación.
  EspLink.print("\",\"simulation\":");

  // Comentario: Escribir booleano JSON de simulación.
  EspLink.print(SIMULATION_MODE ? "true" : "false");

  // Comentario: Añadir estado de caldera.
  EspLink.print(",\"state\":\"");

  // Comentario: Escribir estado normalizado.
  EspLink.print(stateToText(currentState));

  // Comentario: Añadir temperatura de agua.
  EspLink.print("\",\"water_temp\":");

  // Comentario: Escribir temperatura de agua con un decimal.
  EspLink.print(sensors.waterTempC, 1);

  // Comentario: Añadir temperatura de humos.
  EspLink.print(",\"smoke_temp\":");

  // Comentario: Escribir temperatura de humos con un decimal.
  EspLink.print(sensors.smokeTempC, 1);

  // Comentario: Añadir nivel de combustible.
  EspLink.print(",\"fuel_level\":");

  // Comentario: Escribir porcentaje de combustible.
  EspLink.print(sensors.fuelLevelPct);

  // Comentario: Añadir salida de sinfín.
  EspLink.print(",\"auger\":");

  // Comentario: Escribir estado booleano de sinfín.
  EspLink.print(outputs.augerActive ? "true" : "false");

  // Comentario: Añadir salida de bujía.
  EspLink.print(",\"igniter\":");

  // Comentario: Escribir estado booleano de bujía.
  EspLink.print(outputs.igniterActive ? "true" : "false");

  // Comentario: Añadir salida de bomba.
  EspLink.print(",\"pump\":");

  // Comentario: Escribir estado booleano de bomba.
  EspLink.print(outputs.pumpActive ? "true" : "false");

  // Comentario: Añadir ventilador primario.
  EspLink.print(",\"fan_primary_pct\":");

  // Comentario: Escribir porcentaje de ventilador primario.
  EspLink.print(outputs.fanPrimaryPct);

  // Comentario: Añadir ventilador secundario.
  EspLink.print(",\"fan_secondary_pct\":");

  // Comentario: Escribir porcentaje de ventilador secundario.
  EspLink.print(outputs.fanSecondaryPct);

  // Comentario: Añadir versión de configuración activa.
  EspLink.print(",\"config_version\":");

  // Comentario: Escribir versión de configuración activa.
  EspLink.print(activeConfig.configVersion);

  // Comentario: Añadir origen de configuración.
  EspLink.print(",\"config_source\":\"");

  // Comentario: Escribir origen de configuración.
  EspLink.print(configSource);

  // Comentario: Cerrar JSON y línea.
  EspLink.println("\",\"protocol_version\":\"1.1\"}");
}
