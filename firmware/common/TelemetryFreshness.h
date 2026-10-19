// Comentario: Compartir la política de antigüedad local sin depender de WiFi ni Arduino.
#ifndef CALDERA_TELEMETRY_FRESHNESS_H
#define CALDERA_TELEMETRY_FRESHNESS_H
#include <stdint.h>
// Comentario: Retener como máximo una muestra pendiente y nunca publicarla como nueva indefinidamente.
class TelemetryFreshness {
public:
    // Comentario: Quince segundos es un límite del proyecto, no un parámetro de la caldera original.
    static const uint32_t MAX_AGE_MS = 15000;
    // Comentario: Registrar recepción únicamente después de validar identidad y estructura de la trama.
    void received(uint32_t now) { receivedAt = now; pending = true; known = true; }
    // Comentario: Usar resta modular para tolerar el rollover de millis en ventanas menores a 2^31 ms.
    bool ready(uint32_t now) {
        // Comentario: Descartar definitivamente al vencer para que otro rollover no reactive una muestra antigua.
        if (pending && static_cast<uint32_t>(now - receivedAt) > MAX_AGE_MS) { pending = false; }
        return pending;
    }
    // Comentario: Consumir una muestra tras persistencia confirmada; una nueva recepción abre otra entrega.
    void delivered() { pending = false; }
    // Comentario: Distinguir ausencia inicial de Arduino de un enlace que dejó de entregar muestras.
    bool everReceived() const { return known; }
private:
    uint32_t receivedAt = 0;
    bool pending = false;
    bool known = false;
};
#endif
