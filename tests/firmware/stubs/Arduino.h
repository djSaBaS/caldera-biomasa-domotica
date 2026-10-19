// Comentario: Sustituir exclusivamente E/S de hardware para ejecutar el sketch real en el host.
#pragma once
// Comentario: Proporcionar tipos, cadenas, colas UART y reloj controlado por la prueba.
#include <cmath>
#include <cstdint>
#include <deque>
#include <sstream>
#include <string>
#include <algorithm>
// Comentario: Mantener la comprobación de finitud usada por el sketch Arduino.
using std::isfinite;
// Comentario: Reproducir solo las operaciones String usadas por el firmware Mega auditado.
class String {
    std::string value;
public:
    String(const char* text = "") : value(text) {}
    String(const std::string& text) : value(text) {}
    size_t length() const { return value.size(); }
    const char* c_str() const { return value.c_str(); }
    bool startsWith(const char* prefix) const { return value.rfind(prefix, 0) == 0; }
    String substring(size_t start) const { return String(value.substr(start)); }
    String& operator+=(char character) { value += character; return *this; }
    void trim() {
        const auto first = value.find_first_not_of(" \r\n\t");
        value = first == std::string::npos ? "" : value.substr(first, value.find_last_not_of(" \r\n\t") - first + 1);
    }
};
// Comentario: Simular recepción y transmisión UART sin abrir dispositivos ni puertos del host.
class HardwareSerial {
public:
    std::deque<char> incoming;
    std::ostringstream outgoing;
    void begin(unsigned long) {}
    int available() const { return static_cast<int>(incoming.size()); }
    int read() { char value = incoming.front(); incoming.pop_front(); return value; }
    template<class T> void print(const T& value) { outgoing << value; }
    void print(float value, int) { outgoing << value; }
    template<class T> void println(const T& value) { print(value); outgoing << '\n'; }
    void inject(const std::string& value) { incoming.insert(incoming.end(), value.begin(), value.end()); }
};
// Comentario: Proveer las dos UART y un reloj cuyo avance decide la regresión.
inline HardwareSerial Serial;
inline HardwareSerial Serial1;
inline unsigned long hostMillis = 0;
inline unsigned long millis() { return hostMillis; }
