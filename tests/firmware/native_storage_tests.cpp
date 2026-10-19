// Comentario: Probar el diario real con cortes de alimentación en cada frontera de escritura.
#include "ConfigJournal.h"
#include <array>
#include <iostream>
#include <stdexcept>
// Comentario: Simular EEPROM sin modificar el algoritmo productivo y detener escrituras tras un presupuesto.
struct FaultStorage {
    std::array<uint8_t, 512> bytes{};
    unsigned writes = 0;
    unsigned budget = UINT32_MAX;
    uint8_t read(int address) const { return bytes.at(address); }
    void update(int address, uint8_t value) { if (writes++ < budget) { bytes.at(address) = value; } }
};
// Comentario: Detenerse en la primera pérdida de atomicidad o aceptación de corrupción.
static unsigned checks = 0;
void check(bool value, const char* message) {
    ++checks;
    if (!value) { throw std::runtime_error(message); }
}
// Comentario: Cubrir recuperación, desgaste, migración intacta y rollover sin hardware conectado.
int main() {
    try {
        uint32_t first[CONFIG_FIELD_COUNT] = {10,12,50,50,60,75,80,90,10,30,1,3,7,3,7,3,7,3,7,4,6,2,10};
        uint32_t second[CONFIG_FIELD_COUNT];
        memcpy(second, first, sizeof(first));
        second[0] = 11;
        second[2] = 60;
        FaultStorage baseline;
        baseline.bytes.fill(0xFF);
        check(journalStore(baseline, first), "Crear la primera copia");
        // Comentario: Repetir sobre ranuras nuevas y reutilizadas para incluir marcadores antiguos válidos.
        for (unsigned round = 0; round < 3; ++round) {
            for (unsigned cut = 0; cut <= JOURNAL_RECORD_SIZE + 1; ++cut) {
                FaultStorage interrupted = baseline;
                interrupted.writes = 0;
                interrupted.budget = cut;
                const bool committed = journalStore(interrupted, second);
                uint32_t recovered[CONFIG_FIELD_COUNT];
                uint32_t generation = 0;
                uint8_t slot = 0;
                check(journalLoad(interrupted, recovered, generation, slot), "Un corte no debe perder la copia anterior");
                check(memcmp(recovered, committed ? second : first, sizeof(first)) == 0, "Recuperar íntegramente la copia nueva o anterior");
                check(interrupted.bytes[0] == 0xFF && interrupted.bytes[127] == 0xFF, "No tocar el área de migración legada");
            }
            check(journalStore(baseline, second), "Completar actualización entre rondas");
            memcpy(first, second, sizeof(first));
            ++second[0];
        }
        const unsigned writes = baseline.writes;
        check(journalStore(baseline, first) && baseline.writes == writes, "Repetición idempotente sin desgaste");
        // Comentario: Corromper cada byte de la copia más reciente y exigir recuperación de la anterior.
        uint32_t recovered[CONFIG_FIELD_COUNT];
        uint32_t generation = 0;
        uint8_t slot = 0;
        check(journalLoad(baseline, recovered, generation, slot), "Localizar la copia vigente");
        for (unsigned offset = 0; offset < JOURNAL_RECORD_SIZE; ++offset) {
            FaultStorage corrupted = baseline;
            corrupted.bytes[JOURNAL_START + slot * JOURNAL_SLOT_SIZE + offset] ^= 0x01;
            uint32_t previousGeneration = 0;
            uint8_t previousSlot = 0;
            check(journalLoad(corrupted, recovered, previousGeneration, previousSlot) && previousSlot != slot, "CRC o marcador deben detectar cada byte corrupto");
        }
        // Comentario: Preparar generaciones contiguas al rollover manteniendo CRC correcto.
        for (uint8_t index = 0; index < 2; ++index) {
            const unsigned base = JOURNAL_START + index * JOURNAL_SLOT_SIZE;
            journalPut32(baseline.bytes.data() + base + 4, index == slot ? UINT32_MAX : UINT32_MAX - 1);
            journalPut32(baseline.bytes.data() + base + JOURNAL_DATA_SIZE, journalCrc(baseline.bytes.data() + base, JOURNAL_DATA_SIZE));
        }
        check(journalStore(baseline, second), "Escribir generación cero tras rollover");
        check(journalLoad(baseline, recovered, generation, slot) && generation == 0 && recovered[0] == second[0], "Seleccionar generación cero más reciente que UINT32_MAX");
        // Comentario: Un CRC válido no convierte una configuración térmica inválida en aceptable.
        const unsigned base = JOURNAL_START + slot * JOURNAL_SLOT_SIZE;
        journalPut32(baseline.bytes.data() + base + 8 + 7 * 4, 1);
        journalPut32(baseline.bytes.data() + base + JOURNAL_DATA_SIZE, journalCrc(baseline.bytes.data() + base, JOURNAL_DATA_SIZE));
        check(journalLoad(baseline, recovered, generation, slot) && recovered[7] == 90, "Validar rangos también durante recuperación");
        std::cout << "Regresiones EEPROM con fallos inyectados: " << checks << " comprobaciones correctas.\n";
    } catch (const std::exception& error) {
        std::cerr << "FALLO: " << error.what() << '\n';
        return 1;
    }
    return 0;
}
