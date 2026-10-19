# Arquitectura firmware 0.4.7

Ambos sketches fijan `SIMULATION_MODE=true` y lo exigen con `static_assert`. No hay `digitalWrite`, PWM, relés, dimmers ni control de cargas físicas. Desactivar una constante no habilita una implementación operativa.

Mega mantiene sensores simulados, estado de demostración, salidas lógicas y configuración local. `firmware/common/ControlModel.h` contiene parser estricto, límites de transporte y modelo temporal de sinfín. La secuencia general sigue avanzando por reloj cada 15 segundos; no acredita las transiciones originales. La reconstrucción y su matriz están en [máquina de estados](../auditoria/MAQUINA_ESTADOS.md).

Los perfiles ACC/STB/NORMAL/MOD/MAN contienen trabajo y pausa independientes. MAN usa T04 en minutos, T05 en segundos y pausa CP09 antes de CL09 dentro de T05. El valor legado se expande a ON=OFF solo como preferencia del proyecto; MAN sin perfil explícito queda deshabilitado. Las salidas de ventilación continúan con porcentajes generales y la bomba es un modelo térmico sin histéresis original completa.

Una alarma detectada queda enclavada; NaN/Inf también producen fallo. La sobretemperatura corta aporte y encendedor, conservando la disipación simulada con sonda válida. No existe procedimiento de reset implementado ni validación de instalación. Arrancar siempre en OFF y recuperar consignas EEPROM no equivale a REC.

ESP32 lee UART y usa ArduinoJson 7.4.2 para verificar sobre HTTP, protocolo, origen persistido, UID, tipos y límites. Pide respuesta compacta sin catálogo, no aplica fallback remoto y reenvía configuración periódicamente. No consume comandos no implementados ni inventa telemetría cuando falta Mega fuera de simulación. La red sigue deshabilitada en el sketch de simulación; los parsers y el modelo se prueban en host.

Los detalles de framing, unidades, ACK de alcance parcial y migración EEPROM v1→v2 se especifican en [contrato 1.1](API_CONTRACT.md). Las escrituras de EEPROM no están protegidas con doble slot y su resistencia a cortes eléctricos queda pendiente. Los temporizadores usan resta sin signo; eso se prueba en rollover, sin afirmar ausencia de todas las pérdidas de enlace.

Compilación reproducible: Arduino CLI 1.5.1, AVR 1.8.8, ESP32 3.3.12 y ArduinoJson 7.4.2. `compile_firmware.sh` instala versiones fijadas; `ARDUINO_SKIP_INSTALL=1` permite compilar con las ya preparadas. Los headers comunes se copian a los sketches temporales. Las regresiones nativas compilan esos mismos headers y el sketch Mega con UART/reloj/EEPROM simulados; no sustituyen la compilación de placa ni pruebas de hardware.
