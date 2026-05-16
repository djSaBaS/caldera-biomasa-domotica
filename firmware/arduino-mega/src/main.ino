// Comentario: Incluir EEPROM para conservar la última configuración segura sin internet.
#include <EEPROM.h>

// Comentario: Definir versión del firmware Arduino Mega para trazabilidad con backend y telemetría.
const char* FIRMWARE_VERSION = "0.4.3-offline-config";

// Comentario: Mantener siempre activado el modo simulación hasta validar en banco con cargas seguras.
const bool SIMULATION_MODE = true;

// Comentario: Definir identificador lógico compartido con backend y ESP32.
const char* DEVICE_ID = "caldera-01";

// Comentario: Definir puerto serie de comunicación con ESP32 en Arduino Mega.
HardwareSerial& EspLink = Serial1;

// Comentario: Definir velocidad serie estable para el puente Arduino Mega y ESP32.
const unsigned long ESP_LINK_BAUD = 115200;

// Comentario: Definir intervalo base del bucle principal sin usar delay.
const unsigned long LOOP_INTERVAL_MS = 100;

// Comentario: Definir tamaño máximo de línea recibida desde ESP32 para proteger RAM.
const unsigned int ESP_LINE_MAX_LENGTH = 220;

// Comentario: Definir firma persistente para validar contenido EEPROM propio del proyecto.
const uint16_t CONFIG_MAGIC = 0xCA1D;

// Comentario: Definir versión de estructura EEPROM para invalidar formatos antiguos de forma controlada.
const uint8_t CONFIG_SCHEMA_VERSION = 1;

// Comentario: Definir posición inicial de EEPROM para configuración persistente.
const int CONFIG_EEPROM_ADDRESS = 0;

// Comentario: Enumerar estados originales que deben respetarse.
enum BoilerState {
  STATE_OFF,
  STATE_CHECK,
  STATE_ACC,
  STATE_STB,
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
unsigned long lastAugerToggleMillis = 0;

// Comentario: Registrar si existe alarma activa.
bool alarmActive = false;

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
  BoilerConfig config = {CONFIG_MAGIC, CONFIG_SCHEMA_VERSION, 1, 10, 50, 50, 60, 75, 80, 90, 10, 30, 1, 0};

  // Comentario: Calcular checksum antes de devolver valores por defecto.
  config.checksum = calculateConfigChecksum(config);

  // Comentario: Devolver configuración lista para uso y persistencia.
  return config;
}

// Comentario: Cargar configuración desde EEPROM con fallback seguro.
void loadConfigFromEeprom()
{
  // Comentario: Leer estructura completa desde EEPROM.
  EEPROM.get(CONFIG_EEPROM_ADDRESS, activeConfig);

  // Comentario: Validar firma, esquema, checksum y rangos antes de aplicar.
  if (isConfigValid(activeConfig)) {
    // Comentario: Marcar origen persistido para telemetría.
    configSource = "eeprom";

    // Comentario: Salir usando la última configuración válida.
    return;
  }

  // Comentario: Aplicar valores por defecto si EEPROM está vacía o corrupta.
  activeConfig = defaultConfig();

  // Comentario: Marcar origen por defecto para diagnóstico.
  configSource = "defaults";

  // Comentario: Persistir defaults para próximos arranques autónomos.
  persistConfig(activeConfig);
}

// Comentario: Persistir configuración válida en EEPROM.
void persistConfig(BoilerConfig config)
{
  // Comentario: Escribir estructura completa en dirección controlada.
  EEPROM.put(CONFIG_EEPROM_ADDRESS, config);
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
  // Comentario: Rechazar ciclo de sinfín fuera de límites documentados.
  if (config.augerCycleSeconds < 2 || config.augerCycleSeconds > 120) {
    return false;
  }

  // Comentario: Rechazar ventilador primario fuera de porcentaje válido.
  if (config.fanPrimaryPct > 100) {
    return false;
  }

  // Comentario: Rechazar ventilador secundario fuera de porcentaje válido.
  if (config.fanSecondaryPct > 100) {
    return false;
  }

  // Comentario: Rechazar activación de bomba fuera de rango térmico prudente.
  if (config.pumpOnTempC < 35 || config.pumpOnTempC > 75) {
    return false;
  }

  // Comentario: Rechazar objetivo fuera del rango operativo esperado.
  if (config.targetTempC < 55 || config.targetTempC > 85) {
    return false;
  }

  // Comentario: Rechazar mantenimiento fuera de rango operativo esperado.
  if (config.maintenanceTempC < 60 || config.maintenanceTempC > 88) {
    return false;
  }

  // Comentario: Rechazar seguridad fuera de rango o por debajo del objetivo.
  if (config.safetyTempC < 75 || config.safetyTempC > 95 || config.safetyTempC < config.targetTempC) {
    return false;
  }

  // Comentario: Rechazar telemetría demasiado frecuente o demasiado lenta.
  if (config.telemetryIntervalSeconds < 5 || config.telemetryIntervalSeconds > 300) {
    return false;
  }

  // Comentario: Rechazar valor de notificaciones fuera de booleano normalizado.
  if (config.notificationsEnabled > 1) {
    return false;
  }

  // Comentario: Rechazar consulta de configuración fuera del rango documentado.
  if (config.configPollIntervalSeconds < 10 || config.configPollIntervalSeconds > 600) {
    return false;
  }

  // Comentario: Aceptar configuración tras superar todas las reglas.
  return true;
}

// Comentario: Calcular checksum simple con campos explícitos para evitar padding de estructura.
uint16_t calculateConfigChecksum(BoilerConfig config)
{
  // Comentario: Inicializar acumulador no criptográfico suficiente para corrupción accidental.
  uint16_t checksum = 0;

  // Comentario: Incorporar firma de configuración.
  checksum = mixChecksum(checksum, config.magic);

  // Comentario: Incorporar versión de esquema.
  checksum = mixChecksum(checksum, config.schemaVersion);

  // Comentario: Incorporar versión de configuración.
  checksum = mixChecksum(checksum, config.configVersion);

  // Comentario: Incorporar ciclo de sinfín.
  checksum = mixChecksum(checksum, config.augerCycleSeconds);

  // Comentario: Incorporar ventilador primario.
  checksum = mixChecksum(checksum, config.fanPrimaryPct);

  // Comentario: Incorporar ventilador secundario.
  checksum = mixChecksum(checksum, config.fanSecondaryPct);

  // Comentario: Incorporar temperatura de bomba.
  checksum = mixChecksum(checksum, config.pumpOnTempC);

  // Comentario: Incorporar temperatura objetivo.
  checksum = mixChecksum(checksum, config.targetTempC);

  // Comentario: Incorporar temperatura de mantenimiento.
  checksum = mixChecksum(checksum, config.maintenanceTempC);

  // Comentario: Incorporar temperatura de seguridad.
  checksum = mixChecksum(checksum, config.safetyTempC);

  // Comentario: Incorporar intervalo de telemetría.
  checksum = mixChecksum(checksum, config.telemetryIntervalSeconds);

  // Comentario: Incorporar intervalo de consulta.
  checksum = mixChecksum(checksum, config.configPollIntervalSeconds);

  // Comentario: Incorporar bandera de notificaciones.
  checksum = mixChecksum(checksum, config.notificationsEnabled);

  // Comentario: Devolver checksum calculado.
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

  // Comentario: Procesar todos los caracteres disponibles del enlace ESP32.
  while (EspLink.available() > 0) {
    // Comentario: Leer un carácter disponible.
    char character = static_cast<char>(EspLink.read());

    // Comentario: Procesar línea cuando llega salto de línea.
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
  // Comentario: Copiar configuración actual para mantener atomicidad ante errores.
  BoilerConfig candidate = activeConfig;

  // Comentario: Aplicar firma esperada al candidato.
  candidate.magic = CONFIG_MAGIC;

  // Comentario: Aplicar versión de esquema esperada al candidato.
  candidate.schemaVersion = CONFIG_SCHEMA_VERSION;

  // Comentario: Leer versión de configuración remota.
  candidate.configVersion = static_cast<uint16_t>(readUnsignedValue(payload, "version", activeConfig.configVersion));

  // Comentario: Leer ciclo de sinfín remoto.
  candidate.augerCycleSeconds = static_cast<uint16_t>(readUnsignedValue(payload, "auger", activeConfig.augerCycleSeconds));

  // Comentario: Leer ventilador primario remoto.
  candidate.fanPrimaryPct = static_cast<uint8_t>(readUnsignedValue(payload, "fan1", activeConfig.fanPrimaryPct));

  // Comentario: Leer ventilador secundario remoto.
  candidate.fanSecondaryPct = static_cast<uint8_t>(readUnsignedValue(payload, "fan2", activeConfig.fanSecondaryPct));

  // Comentario: Leer temperatura de bomba remota.
  candidate.pumpOnTempC = static_cast<uint8_t>(readUnsignedValue(payload, "pump", activeConfig.pumpOnTempC));

  // Comentario: Leer temperatura objetivo remota.
  candidate.targetTempC = static_cast<uint8_t>(readUnsignedValue(payload, "target", activeConfig.targetTempC));

  // Comentario: Leer temperatura de mantenimiento remota.
  candidate.maintenanceTempC = static_cast<uint8_t>(readUnsignedValue(payload, "maintenance", activeConfig.maintenanceTempC));

  // Comentario: Leer temperatura de seguridad remota.
  candidate.safetyTempC = static_cast<uint8_t>(readUnsignedValue(payload, "safety", activeConfig.safetyTempC));

  // Comentario: Leer intervalo de telemetría remoto.
  candidate.telemetryIntervalSeconds = static_cast<uint16_t>(readUnsignedValue(payload, "telemetry", activeConfig.telemetryIntervalSeconds));

  // Comentario: Leer intervalo de consulta remota.
  candidate.configPollIntervalSeconds = static_cast<uint16_t>(readUnsignedValue(payload, "poll", activeConfig.configPollIntervalSeconds));

  // Comentario: Leer activación de notificaciones remota.
  candidate.notificationsEnabled = static_cast<uint8_t>(readUnsignedValue(payload, "notifications", activeConfig.notificationsEnabled));

  // Comentario: Recalcular checksum del candidato antes de validarlo.
  candidate.checksum = calculateConfigChecksum(candidate);

  // Comentario: Rechazar configuración si no supera reglas locales.
  if (!isConfigValid(candidate)) {
    sendConfigAck(false, "configuracion_fuera_de_rango");
    return;
  }

  // Comentario: Activar configuración validada en memoria.
  activeConfig = candidate;

  // Comentario: Marcar origen remoto persistido.
  configSource = "remote_cached";

  // Comentario: Persistir configuración para funcionamiento sin internet.
  persistConfig(activeConfig);

  // Comentario: Confirmar aplicación correcta al ESP32.
  sendConfigAck(true, "configuracion_aplicada_y_cacheada");
}

// Comentario: Leer entero sin signo desde payload clave-valor separado por punto y coma.
unsigned long readUnsignedValue(const String& payload, const char* key, unsigned long fallback)
{
  // Comentario: Construir patrón de búsqueda exacto.
  String pattern = String(key) + "=";

  // Comentario: Buscar patrón dentro del payload.
  int startIndex = payload.indexOf(pattern);

  // Comentario: Devolver fallback si la clave no existe.
  if (startIndex < 0) {
    return fallback;
  }

  // Comentario: Mover índice al inicio del valor.
  startIndex += pattern.length();

  // Comentario: Buscar final de valor por separador.
  int endIndex = payload.indexOf(';', startIndex);

  // Comentario: Usar final de cadena si no hay separador posterior.
  if (endIndex < 0) {
    endIndex = payload.length();
  }

  // Comentario: Extraer valor textual.
  String value = payload.substring(startIndex, endIndex);

  // Comentario: Normalizar espacios residuales.
  value.trim();

  // Comentario: Devolver fallback si el valor quedó vacío.
  if (value.length() == 0) {
    return fallback;
  }

  // Comentario: Convertir a entero sin signo usando utilidad Arduino.
  return static_cast<unsigned long>(value.toInt());
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
  EspLink.print(activeConfig.configVersion);

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
    case STATE_CHECK: return "CHECK";
    case STATE_ACC: return "ACC";
    case STATE_STB: return "STB";
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
  bool sensorFailure = !sensors.criticalSensorOk;

  // Comentario: Detectar sobretemperatura usando configuración local cacheada.
  bool overTemperature = sensors.waterTempC >= activeConfig.safetyTempC;

  // Comentario: Detectar puerta abierta o termostato de seguridad disparado.
  bool safetyChainOpen = !sensors.doorClosed || !sensors.safetyThermostatOk;

  // Comentario: Activar alarma si cualquier condición crítica aparece.
  alarmActive = sensorFailure || overTemperature || safetyChainOpen;

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
  // Comentario: Apagar todo si hay alarma o modo simulación impide salidas reales.
  if (alarmActive || SIMULATION_MODE) {
    // Comentario: Mantener bomba simulada si temperatura supera umbral configurado y no hay alarma.
    outputs.pumpActive = !alarmActive && sensors.waterTempC >= activeConfig.pumpOnTempC;

    // Comentario: Calcular ventilador primario desde configuración activa solo en combustión.
    outputs.fanPrimaryPct = isCombustionState() ? activeConfig.fanPrimaryPct : 0;

    // Comentario: Calcular ventilador secundario desde configuración activa solo en combustión.
    outputs.fanSecondaryPct = isCombustionState() ? activeConfig.fanSecondaryPct : 0;

    // Comentario: Mantener bujía simulada únicamente en ACC.
    outputs.igniterActive = currentState == STATE_ACC && !alarmActive;

    // Comentario: Actualizar sinfín temporizado con configuración activa.
    updateAugerCycle(currentMillis);

    // Comentario: Salir sin escribir pines físicos.
    return;
  }

  // Comentario: En fases futuras se escribirán pines físicos tras validaciones adicionales.
  forceOutputsOff();
}

// Comentario: Actualizar ciclo temporizado del sinfín con ON igual a OFF.
void updateAugerCycle(unsigned long currentMillis)
{
  // Comentario: Desactivar sinfín fuera de estados de combustión o ante alarma.
  if (!isCombustionState() || alarmActive) {
    outputs.augerActive = false;
    lastAugerToggleMillis = currentMillis;
    return;
  }

  // Comentario: Convertir segundos configurados a milisegundos.
  unsigned long augerCycleMs = static_cast<unsigned long>(activeConfig.augerCycleSeconds) * 1000UL;

  // Comentario: Conmutar sinfín cuando se cumple el periodo configurado.
  if (currentMillis - lastAugerToggleMillis >= augerCycleMs) {
    outputs.augerActive = !outputs.augerActive;
    lastAugerToggleMillis = currentMillis;
  }
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
  EspLink.println("\"}");
}
