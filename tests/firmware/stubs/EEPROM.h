// Comentario: Modelar almacenamiento EEPROM en RAM para probar integridad y migración sin placas.
#pragma once
// Comentario: Usar memoria acotada y copias de bytes con el mismo contrato get/put.
#include <array>
#include <cstring>
class EepromStub {
public:
    std::array<unsigned char, 4096> bytes = {};
    unsigned writes = 0;
    template<class T> void get(int address, T& value) { std::memcpy(&value, bytes.data() + address, sizeof(T)); }
    template<class T> void put(int address, const T& value) { std::memcpy(bytes.data() + address, &value, sizeof(T)); ++writes; }
};
// Comentario: Proveer el nombre que utiliza la librería Arduino original.
inline EepromStub EEPROM;
