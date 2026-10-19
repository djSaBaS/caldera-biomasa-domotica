// Comentario: Proteger la inclusión del diario portable de configuración.
#ifndef CALDERA_CONFIG_JOURNAL_H
#define CALDERA_CONFIG_JOURNAL_H
// Comentario: Validar la configuración recuperada con el mismo contrato del parser UART.
#include "ControlModel.h"

// Comentario: Reservar dos ranuras separadas del layout legado situado en la dirección cero.
static const uint16_t JOURNAL_START = 128;
static const uint16_t JOURNAL_SLOT_SIZE = 128;
// Comentario: Guardar firma, generación, valores uint32, CRC32 y marcador final sin padding.
static const uint16_t JOURNAL_DATA_SIZE = 8 + CONFIG_FIELD_COUNT * 4;
static const uint16_t JOURNAL_RECORD_SIZE = JOURNAL_DATA_SIZE + 5;
static_assert(JOURNAL_RECORD_SIZE <= JOURNAL_SLOT_SIZE, "Registro demasiado grande");

// Comentario: Codificar enteros en little endian explícito para que AVR y host compartan formato.
inline void journalPut32(uint8_t* bytes, uint32_t value)
{
    for (uint8_t i = 0; i < 4; ++i) { bytes[i] = static_cast<uint8_t>(value >> (8U * i)); }
}
// Comentario: Reconstruir un entero sin depender de alineación ni arquitectura.
inline uint32_t journalGet32(const uint8_t* bytes)
{
    uint32_t value = 0;
    for (uint8_t i = 0; i < 4; ++i) { value |= static_cast<uint32_t>(bytes[i]) << (8U * i); }
    return value;
}
// Comentario: Detectar corrupción accidental de firma, generación y campos con CRC32 IEEE.
inline uint32_t journalCrc(const uint8_t* bytes, uint16_t size)
{
    uint32_t crc = UINT32_MAX;
    for (uint16_t i = 0; i < size; ++i) {
        crc ^= bytes[i];
        for (uint8_t bit = 0; bit < 8; ++bit) { crc = (crc >> 1) ^ ((crc & 1U) ? 0xEDB88320UL : 0U); }
    }
    return crc ^ UINT32_MAX;
}
// Comentario: Leer una ranura solo si el marcador final, la firma, el CRC y los rangos coinciden.
template<class Storage>
bool journalReadSlot(Storage& storage, uint8_t slot, uint32_t* values, uint32_t& generation)
{
    // Comentario: No admitir índices que salgan del área reservada del diario.
    if (slot > 1) { return false; }
    const uint16_t base = JOURNAL_START + slot * JOURNAL_SLOT_SIZE;
    if (storage.read(base + JOURNAL_RECORD_SIZE - 1) != 0xA5) { return false; }
    uint8_t bytes[JOURNAL_DATA_SIZE + 4];
    for (uint16_t i = 0; i < sizeof(bytes); ++i) { bytes[i] = storage.read(base + i); }
    // Comentario: La firma CBJ1 identifica el formato de registro, distinto del esquema lógico EEPROM v2.
    if (memcmp(bytes, "CBJ1", 4) != 0 || journalGet32(bytes + JOURNAL_DATA_SIZE) != journalCrc(bytes, JOURNAL_DATA_SIZE)) { return false; }
    uint32_t candidate[CONFIG_FIELD_COUNT];
    for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) { candidate[i] = journalGet32(bytes + 8 + i * 4); }
    if (!validConfigValues(candidate)) { return false; }
    // Comentario: No modificar la salida del lector hasta completar todas las comprobaciones.
    memcpy(values, candidate, sizeof(candidate));
    generation = journalGet32(bytes + 4);
    return true;
}
// Comentario: Recuperar la última ranura válida tolerando corrupción o escritura interrumpida de la otra.
template<class Storage>
bool journalLoad(Storage& storage, uint32_t* values, uint32_t& generation, uint8_t& slot)
{
    uint32_t first[CONFIG_FIELD_COUNT];
    uint32_t second[CONFIG_FIELD_COUNT];
    uint32_t firstGeneration = 0;
    uint32_t secondGeneration = 0;
    const bool firstOk = journalReadSlot(storage, 0, first, firstGeneration);
    const bool secondOk = journalReadSlot(storage, 1, second, secondGeneration);
    if (!firstOk && !secondOk) { return false; }
    // Comentario: Comparar generaciones con aritmética modular para tolerar rollover de uint32.
    const uint32_t difference = secondGeneration - firstGeneration;
    const bool useSecond = secondOk && (!firstOk || (difference != 0 && difference < 0x80000000UL));
    memcpy(values, useSecond ? second : first, sizeof(first));
    generation = useSecond ? secondGeneration : firstGeneration;
    slot = useSecond ? 1 : 0;
    return true;
}
// Comentario: Escribir la ranura inactiva y publicar su validez únicamente en el último byte.
template<class Storage>
bool journalStore(Storage& storage, const uint32_t* values)
{
    if (!validConfigValues(values)) { return false; }
    uint32_t previous[CONFIG_FIELD_COUNT];
    uint32_t generation = 0;
    uint8_t slot = 1;
    const bool existing = journalLoad(storage, previous, generation, slot);
    // Comentario: Evitar desgaste de EEPROM si el contenido lógico no ha cambiado.
    if (existing && memcmp(values, previous, sizeof(previous)) == 0) { return true; }
    const uint16_t base = JOURNAL_START + (1 - slot) * JOURNAL_SLOT_SIZE;
    uint8_t bytes[JOURNAL_DATA_SIZE + 4];
    memcpy(bytes, "CBJ1", 4);
    journalPut32(bytes + 4, generation + 1);
    for (uint8_t i = 0; i < CONFIG_FIELD_COUNT; ++i) { journalPut32(bytes + 8 + i * 4, values[i]); }
    journalPut32(bytes + JOURNAL_DATA_SIZE, journalCrc(bytes, JOURNAL_DATA_SIZE));
    // Comentario: Invalidar primero el destino; nunca modificar la ranura que contiene la copia vigente.
    storage.update(base + JOURNAL_RECORD_SIZE - 1, 0);
    for (uint16_t i = 0; i < sizeof(bytes); ++i) { storage.update(base + i, bytes[i]); }
    storage.update(base + JOURNAL_RECORD_SIZE - 1, 0xA5);
    // Comentario: Exigir lectura idéntica antes de comunicar persistencia confirmada.
    uint32_t verified[CONFIG_FIELD_COUNT];
    uint32_t verifiedGeneration = 0;
    return journalReadSlot(storage, 1 - slot, verified, verifiedGeneration) && verifiedGeneration == generation + 1 && memcmp(values, verified, sizeof(verified)) == 0;
}
#endif
