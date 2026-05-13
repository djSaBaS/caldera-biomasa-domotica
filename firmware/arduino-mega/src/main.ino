// Comentario: Definir versión del firmware Arduino Mega para trazabilidad.
const char* FIRMWARE_VERSION = "0.3.0-sprint-02-persistencia-auth";

// Comentario: Mantener siempre activado el modo simulación durante esta fase.
const bool SIMULATION_MODE = true;

// Comentario: Definir intervalo base del bucle principal sin usar delay().
const unsigned long LOOP_INTERVAL_MS = 100;

// Comentario: Definir intervalo de telemetría simulada hacia ESP32.
const unsigned long TELEMETRY_INTERVAL_MS = 5000;

// Comentario: Definir ciclo inicial del sinfín con tiempo ON igual a OFF.
const unsigned long AUGER_CYCLE_MS = 10000;

// Comentario: Definir temperatura de seguridad simulada.
const float SAFETY_TEMP_C = 90.0;

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

// Comentario: Agrupar sensores críticos simulados.
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

// Comentario: Guardar estado actual de la caldera.
BoilerState currentState = STATE_OFF;

// Comentario: Guardar sensores simulados con valores seguros.
SensorState sensors = {72.5, 210.0, 78, true, true, true};

// Comentario: Guardar salidas simuladas inicialmente apagadas.
OutputState outputs = {false, false, false, 0, 0};

// Comentario: Registrar última ejecución del bucle lógico.
unsigned long lastLoopMillis = 0;

// Comentario: Registrar última telemetría enviada.
unsigned long lastTelemetryMillis = 0;

// Comentario: Registrar última conmutación del sinfín simulado.
unsigned long lastAugerToggleMillis = 0;

// Comentario: Registrar si existe alarma activa.
bool alarmActive = false;

// Comentario: Inicializar firmware en estado seguro.
void setup()
{
  // Comentario: Inicializar comunicación serie con ESP32 y monitor de depuración.
  Serial.begin(115200);

  // Comentario: Esperar brevemente a que el puerto serie esté disponible en placas compatibles.
  while (!Serial) {
    // Comentario: Mantener espera corta únicamente durante arranque de depuración.
    ;
  }

  // Comentario: Registrar versión y modo de seguridad.
  Serial.println("Arduino Mega iniciado en modo simulacion segura");

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

  // Comentario: Aplicar salidas en simulación segura.
  applyOutputs(currentMillis);

  // Comentario: Enviar telemetría periódica hacia ESP32.
  sendStatusToEsp32(currentMillis);
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

  // Comentario: Mantener temperatura de agua dentro de rango normal simulado.
  sensors.waterTempC = 68.0 + (simulatedWave * 8.0);

  // Comentario: Mantener temperatura de humos dentro de rango operativo simulado.
  sensors.smokeTempC = 180.0 + (simulatedWave * 60.0);

  // Comentario: Mantener nivel de combustible decreciente simulado sin llegar a cero.
  sensors.fuelLevelPct = 78;
}

// Comentario: Evaluar reglas críticas de seguridad local.
void evaluateSafety()
{
  // Comentario: Detectar fallo de sensor crítico.
  bool sensorFailure = !sensors.criticalSensorOk;

  // Comentario: Detectar sobretemperatura de agua.
  bool overTemperature = sensors.waterTempC >= SAFETY_TEMP_C;

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
    // Comentario: Mantener bomba simulada si temperatura supera umbral y no hay alarma.
    outputs.pumpActive = !alarmActive && sensors.waterTempC >= 60.0;

    // Comentario: Calcular ventiladores simulados solo en estados operativos.
    outputs.fanPrimaryPct = isCombustionState() ? 55 : 0;

    // Comentario: Calcular ventilador secundario simulado solo en estados operativos.
    outputs.fanSecondaryPct = isCombustionState() ? 45 : 0;

    // Comentario: Mantener bujía simulada únicamente en ACC.
    outputs.igniterActive = currentState == STATE_ACC && !alarmActive;

    // Comentario: Actualizar sinfín temporizado en simulación.
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

  // Comentario: Conmutar sinfín cuando se cumple el periodo configurado.
  if (currentMillis - lastAugerToggleMillis >= AUGER_CYCLE_MS) {
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

// Comentario: Enviar estado JSON compacto hacia ESP32 por serie.
void sendStatusToEsp32(unsigned long currentMillis)
{
  // Comentario: Respetar intervalo de telemetría para no saturar comunicación serie.
  if (currentMillis - lastTelemetryMillis < TELEMETRY_INTERVAL_MS) {
    return;
  }

  // Comentario: Actualizar marca temporal de último envío.
  lastTelemetryMillis = currentMillis;

  // Comentario: Construir telemetría JSON manual simple para evitar dependencias.
  Serial.print("{\"device_id\":\"caldera-01\",\"firmware\":\"");
  Serial.print(FIRMWARE_VERSION);
  Serial.print("\",\"simulation\":");
  Serial.print(SIMULATION_MODE ? "true" : "false");
  Serial.print(",\"state\":\"");
  Serial.print(stateToText(currentState));
  Serial.print("\",\"water_temp\":");
  Serial.print(sensors.waterTempC, 1);
  Serial.print(",\"smoke_temp\":");
  Serial.print(sensors.smokeTempC, 1);
  Serial.print(",\"fuel_level\":");
  Serial.print(sensors.fuelLevelPct);
  Serial.print(",\"auger\":");
  Serial.print(outputs.augerActive ? "true" : "false");
  Serial.print(",\"igniter\":");
  Serial.print(outputs.igniterActive ? "true" : "false");
  Serial.print(",\"pump\":");
  Serial.print(outputs.pumpActive ? "true" : "false");
  Serial.print(",\"fan_primary_pct\":");
  Serial.print(outputs.fanPrimaryPct);
  Serial.print(",\"fan_secondary_pct\":");
  Serial.print(outputs.fanSecondaryPct);
  Serial.println("}");
}
