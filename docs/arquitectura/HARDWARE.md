# Hardware

## Hardware declarado en el repositorio (sin comprobación física)

- Elegoo Mega 2560 R3.
- Pantalla LCD1602.
- Módulo de 4 relés JQC-3FF-S-Z.
- 2 módulos Robotdyn AC Light Dimmer con zero-cross.
- ESP32.
- ESP32-CAM.
- Relé con módulo ESP32.
- Servos.
- Motor paso a paso con driver.
- Sensores varios pendientes de inventario final.

## Hardware recomendado adicional

- Segundo módulo de relés o relés/contactores adecuados para cargas de 230V.
- Termopar tipo K con módulo MAX31855 o MAX6675 para humos.
- DS18B20 encapsulado metálico para agua.
- Sensor de nivel de combustible, preferiblemente robusto para polvo.
- Fuente de alimentación 5V estable para lógica.
- Fuente separada si hay actuadores de 12V.
- Caja eléctrica con carril DIN, bornas, fusibles y prensaestopas.
- Selector físico ORIGINAL / DOMÓTICO.
- Setas de emergencia o corte manual si el montaje lo requiere.

## Actuadores previstos

- Motor del sinfín.
- Bujía incandescente.
- Ventilador primario.
- Ventilador secundario.
- Bomba de circulación.
- Servo de trampilla en fase futura.

## Sensores previstos

- Temperatura de agua.
- Temperatura de humos.
- Estado de puerta.
- Termostato de seguridad.
- Nivel de combustible.
- Temperatura ambiente opcional.

## Consideraciones eléctricas

La parte de 230V debe montarse con criterio eléctrico real. No se debe dejar en protoboard ni con conexiones Dupont.

Los módulos de relé de bajo coste pueden servir para pruebas, pero para instalación final conviene valorar contactores, SSR adecuados o relés industriales según carga real.

Los dimmers AC con zero-cross deben probarse primero con carga controlada, nunca directamente en la caldera sin banco de pruebas.

## Inventario de auditoría y validación pendiente

| Grupo | Referencia conocida | Evidencia actual | Pendiente |
| --- | --- | --- | --- |
| Control local | Elegoo Mega 2560 R3 / ATmega2560 | Declaración heredada; compilación AVR | Revisión, alimentación, watchdog y estado seguro de arranque |
| Puente | ESP32; ESP32-CAM y relé ESP32 auxiliares | UART GPIO16/17 en sketch genérico | Modelo/revisión, ocupación de pines, CA/TLS y niveles |
| Interfaz local | LCD1602 | Declarada, sin driver de aplicación | Interfaz, dirección/pines y consumo |
| Potencia | 4 relés JQC-3FF-S-Z, 2 dimmers Robotdyn AC zero-cross | Referencias declaradas; sin pinout habilitado | Ratings reales, aislamiento, compatibilidad con motores y protecciones |
| Transporte de combustible | Sinfín(es) de la caldera | Manual ES 8/10 y tablas ES 20 / IT 34 describen dos canales | Identificar uno/dos motores, tensión, corriente, secuencia y retorno de llama |
| Aire y circulación | Ventiladores primario/secundario, bomba, encendedor | Descritos por tablas, sin identificación instalada | Modelos, cargas, interbloqueos y realimentación |
| Sensores críticos | Agua, humos, puerta, termostato, nivel | Solo valores/flags simulados | Tecnología original, rango, cableado, fallo abierto/corto y calibración |
| Recomendaciones nuevas | MAX31855/MAX6675, DS18B20, fuente, caja y selector | Propuestas del documento, no piezas confirmadas | Compatibilidad técnica y validación; no asumir sustitución directa de sondas originales |
| Auxiliares | Servos, paso a paso y driver | Declarados sin modelo/cantidad | Inventario y utilidad; sin integrar en seguridad |

No se han confirmado cantidades, números de serie, ratings ni existencia física. La tabla separa referencias declaradas de propuestas. No existe lista de materiales aprobada para instalación. Las protecciones eléctricas, hidráulicas y térmicas independientes y el respaldo original necesitan diseño y validación profesional antes de conectar cargas.
